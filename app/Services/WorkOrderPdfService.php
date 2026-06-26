<?php

namespace App\Services;

use App\Models\WorkOrder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WorkOrderPdfService
{
    public function generate(WorkOrder $workOrder): string
    {
        $workOrder->loadMissing(['customer', 'installation', 'equipment', 'technician', 'notice', 'review']);

        $signatureImage = $this->signatureImage($workOrder);
        $pdf = $this->buildPdf($workOrder, $signatureImage);
        $path = "work-orders/{$workOrder->id}/parte-{$workOrder->id}.pdf";

        if (! Storage::disk('public')->put($path, $pdf)) {
            throw new RuntimeException('No se pudo guardar el PDF del parte de trabajo.');
        }

        $workOrder->forceFill(['pdf_path' => $path])->saveQuietly();

        return $path;
    }

    /**
     * @param array{width: int, height: int, data: string, filter: string}|null $signatureImage
     */
    private function buildPdf(WorkOrder $workOrder, ?array $signatureImage): string
    {
        $content = '';
        $y = 800;

        $this->addText($content, 50, $y, "Parte de trabajo #{$workOrder->id}", 18);
        $y -= 30;

        $rows = [
            'Numero de parte' => (string) $workOrder->id,
            'Fecha aviso' => $this->formatDate($workOrder->notice?->scheduled_at ?: $workOrder->started_at),
            'Fecha inicio' => $this->formatDate($workOrder->started_at),
            'Fecha fin' => $this->formatDate($workOrder->finished_at),
            'Cliente' => $workOrder->customer?->legal_name,
            'Instalacion' => $workOrder->installation?->name,
            'Direccion' => $workOrder->installation?->address,
            'Equipo' => $workOrder->equipment?->name,
            'Tecnico' => $workOrder->technician?->name,
            'Origen' => $workOrder->origin_label,
            'Resultado' => $this->resultLabel($workOrder->result),
            'Nombre firmante' => $workOrder->customer_name,
        ];

        foreach ($rows as $label => $value) {
            $this->addWrappedLine($content, $y, "{$label}: ".($value ?: '-'));
        }

        $y -= 8;
        $this->addSection($content, $y, 'Descripcion del aviso', $workOrder->notice?->description ?: $workOrder->observations);
        $this->addSection($content, $y, 'Trabajo realizado', $workOrder->work_performed);

        $y -= 8;
        $this->addText($content, 50, $y, 'Firma', 12);
        $y -= 16;

        if ($signatureImage) {
            $maxWidth = 190;
            $maxHeight = 70;
            $scale = min($maxWidth / $signatureImage['width'], $maxHeight / $signatureImage['height'], 1);
            $drawWidth = round($signatureImage['width'] * $scale, 2);
            $drawHeight = round($signatureImage['height'] * $scale, 2);
            $imageY = max(40, $y - $drawHeight);
            $content .= sprintf("q %.2F 0 0 %.2F 50 %.2F cm /Im1 Do Q\n", $drawWidth, $drawHeight, $imageY);
        } else {
            $this->addWrappedLine($content, $y, 'Sin firma.');
        }

        return $this->pdfDocument($content, $signatureImage);
    }

    private function addSection(string &$content, int &$y, string $title, ?string $text): void
    {
        $this->addText($content, 50, $y, $title, 12);
        $y -= 16;

        $lines = $this->wrap($text ?: '-', 95);
        foreach (array_slice($lines, 0, 8) as $line) {
            $this->addText($content, 60, $y, $line, 10);
            $y -= 13;
        }

        if (count($lines) > 8) {
            $this->addText($content, 60, $y, '...', 10);
            $y -= 13;
        }

        $y -= 6;
    }

    private function addWrappedLine(string &$content, int &$y, string $line): void
    {
        foreach ($this->wrap($line, 100) as $wrappedLine) {
            $this->addText($content, 50, $y, $wrappedLine, 10);
            $y -= 13;
        }
    }

    private function addText(string &$content, int $x, int|float $y, string $text, int $size = 10): void
    {
        $content .= sprintf("BT /F1 %d Tf %d %.2F Td (%s) Tj ET\n", $size, $x, $y, $this->encodeText($text));
    }

    /**
     * @return array<int, string>
     */
    private function wrap(?string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $text) ?: '');

        if ($text === '') {
            return ['-'];
        }

        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private function encodeText(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        if ($encoded === false) {
            $encoded = $text;
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }

    /**
     * @param array{width: int, height: int, data: string, filter: string}|null $signatureImage
     */
    private function pdfDocument(string $content, ?array $signatureImage): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';

        $resources = '<< /Font << /F1 4 0 R >>';
        if ($signatureImage) {
            $resources .= ' /XObject << /Im1 6 0 R >>';
        }
        $resources .= ' >>';

        $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources {$resources} /Contents 5 0 R >>";
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[5] = "<< /Length ".strlen($content)." >>\nstream\n{$content}endstream";

        if ($signatureImage) {
            $objects[6] = "<< /Type /XObject /Subtype /Image /Width {$signatureImage['width']} /Height {$signatureImage['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /{$signatureImage['filter']} /Length ".strlen($signatureImage['data'])." >>\nstream\n{$signatureImage['data']}\nendstream";
        }

        $pdf = "%PDF-1.4\n%".chr(226).chr(227).chr(207).chr(211)."\n";
        $offsets = [0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_keys($objects) as $number) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }

    /**
     * @return array{width: int, height: int, data: string, filter: string}|null
     */
    private function signatureImage(WorkOrder $workOrder): ?array
    {
        if (! $workOrder->customer_signature_path) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($workOrder->customer_signature_path)) {
            return null;
        }

        $path = $disk->path($workOrder->customer_signature_path);

        if (! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        $mime = $info['mime'] ?? null;

        return match ($mime) {
            'image/png' => $this->pngToRgbImage($path),
            'image/jpeg' => $this->jpegToPdfImage($path, (int) $info[0], (int) $info[1]),
            default => null,
        };
    }

    /**
     * @return array{width: int, height: int, data: string, filter: string}|null
     */
    private function jpegToPdfImage(string $path, int $width, int $height): ?array
    {
        $data = file_get_contents($path);

        if ($data === false || $width <= 0 || $height <= 0) {
            return null;
        }

        return [
            'width' => $width,
            'height' => $height,
            'data' => $data,
            'filter' => 'DCTDecode',
        ];
    }

    /**
     * @return array{width: int, height: int, data: string, filter: string}|null
     */
    private function pngToRgbImage(string $path): ?array
    {
        $png = file_get_contents($path);

        if ($png === false || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        $offset = 8;
        $width = $height = $bitDepth = $colorType = $interlace = null;
        $idat = '';

        while ($offset + 8 <= strlen($png)) {
            $length = unpack('N', substr($png, $offset, 4))[1];
            $type = substr($png, $offset + 4, 4);
            $data = substr($png, $offset + 8, $length);
            $offset += 12 + $length;

            if ($type === 'IHDR') {
                $header = unpack('Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace', $data);
                $width = $header['width'];
                $height = $header['height'];
                $bitDepth = $header['bitDepth'];
                $colorType = $header['colorType'];
                $interlace = $header['interlace'];
            } elseif ($type === 'IDAT') {
                $idat .= $data;
            } elseif ($type === 'IEND') {
                break;
            }
        }

        if (! $width || ! $height || $bitDepth !== 8 || $interlace !== 0 || ! in_array($colorType, [0, 2, 4, 6], true)) {
            return null;
        }

        $raw = @gzuncompress($idat);

        if ($raw === false) {
            return null;
        }

        $channels = match ($colorType) {
            0 => 1,
            4 => 2,
            2 => 3,
            6 => 4,
        };
        $rowBytes = $width * $channels;
        $position = 0;
        $previous = array_fill(0, $rowBytes, 0);
        $rgb = '';

        for ($rowNumber = 0; $rowNumber < $height; $rowNumber++) {
            if ($position + 1 + $rowBytes > strlen($raw)) {
                return null;
            }

            $filter = ord($raw[$position]);
            $scanline = substr($raw, $position + 1, $rowBytes);
            $position += 1 + $rowBytes;
            $row = [];

            for ($i = 0; $i < $rowBytes; $i++) {
                $value = ord($scanline[$i]);
                $left = $i >= $channels ? $row[$i - $channels] : 0;
                $up = $previous[$i] ?? 0;
                $upLeft = $i >= $channels ? ($previous[$i - $channels] ?? 0) : 0;

                $row[$i] = match ($filter) {
                    0 => $value,
                    1 => ($value + $left) & 0xff,
                    2 => ($value + $up) & 0xff,
                    3 => ($value + intdiv($left + $up, 2)) & 0xff,
                    4 => ($value + $this->paeth($left, $up, $upLeft)) & 0xff,
                    default => $value,
                };
            }

            $previous = $row;

            for ($x = 0; $x < $width; $x++) {
                $base = $x * $channels;

                if ($colorType === 0) {
                    $rgb .= chr($row[$base]).chr($row[$base]).chr($row[$base]);
                } elseif ($colorType === 4) {
                    $gray = $this->blendWithWhite($row[$base], $row[$base + 1]);
                    $rgb .= chr($gray).chr($gray).chr($gray);
                } elseif ($colorType === 6) {
                    $rgb .= chr($this->blendWithWhite($row[$base], $row[$base + 3]))
                        .chr($this->blendWithWhite($row[$base + 1], $row[$base + 3]))
                        .chr($this->blendWithWhite($row[$base + 2], $row[$base + 3]));
                } else {
                    $rgb .= chr($row[$base]).chr($row[$base + 1]).chr($row[$base + 2]);
                }
            }
        }

        return [
            'width' => $width,
            'height' => $height,
            'data' => gzcompress($rgb),
            'filter' => 'FlateDecode',
        ];
    }

    private function blendWithWhite(int $value, int $alpha): int
    {
        if ($alpha >= 255) {
            return $value;
        }

        if ($alpha <= 0) {
            return 255;
        }

        return (int) round((($value * $alpha) + (255 * (255 - $alpha))) / 255);
    }

    private function paeth(int $left, int $up, int $upLeft): int
    {
        $estimate = $left + $up - $upLeft;
        $leftDistance = abs($estimate - $left);
        $upDistance = abs($estimate - $up);
        $upLeftDistance = abs($estimate - $upLeft);

        if ($leftDistance <= $upDistance && $leftDistance <= $upLeftDistance) {
            return $left;
        }

        return $upDistance <= $upLeftDistance ? $up : $upLeft;
    }

    private function formatDate(mixed $date): string
    {
        return $date ? $date->format('d/m/Y H:i') : '-';
    }

    private function resultLabel(?string $result): string
    {
        return [
            'pending' => 'Pendiente',
            'solved' => 'Solucionado',
            'unresolved' => 'No solucionado',
            'not_solved' => 'No solucionado',
            'cancelled' => 'Anulado',
        ][$result] ?? ($result ?: '-');
    }
}
