<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Parte de trabajo #{{ $workOrder->id }}</title>
<style>
    @page {
        margin: 90px 36px 70px 36px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        color: #172033;
        font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
        font-size: 11px;
        line-height: 1.45;
    }

    .header {
        position: fixed;
        top: -78px;
        left: 0;
        right: 0;
        height: 70px;
    }

    .header table {
        width: 100%;
        border-collapse: collapse;
    }

    .header .logo-cell {
        width: 60px;
        vertical-align: middle;
    }

    .header .logo-cell img {
        width: 52px;
        height: 52px;
    }

    .header .brand-cell {
        vertical-align: middle;
        padding-left: 10px;
    }

    .header .brand-name {
        margin: 0;
        color: #173f8a;
        font-size: 17px;
        font-weight: 900;
        letter-spacing: 0.2px;
    }

    .header .brand-sub {
        margin: 2px 0 0;
        color: #667085;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .header .doc-cell {
        vertical-align: middle;
        text-align: right;
        width: 220px;
    }

    .header .doc-title {
        margin: 0;
        color: #172033;
        font-size: 14px;
        font-weight: 900;
    }

    .header .doc-number {
        margin: 2px 0 0;
        color: #0f62fe;
        font-size: 11px;
        font-weight: 800;
    }

    .header-rule {
        position: fixed;
        top: -8px;
        left: 0;
        right: 0;
        height: 4px;
        background-image: linear-gradient(90deg, #0f62fe, #ffd34d);
        background-color: #0f62fe;
    }

    .footer {
        position: fixed;
        bottom: -56px;
        left: 0;
        right: 0;
        height: 40px;
        border-top: 1px solid #d7deea;
        padding-top: 8px;
        color: #98a2b3;
        font-size: 8.5px;
    }

    .footer table {
        width: 100%;
    }

    .footer .footer-right {
        text-align: right;
    }

    .status-badge {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 9px;
        background-color: #e8eefb;
        color: #173f8a;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .status-badge.solved {
        background-color: #dff7ea;
        color: #0f8a5f;
    }

    .status-badge.unresolved,
    .status-badge.cancelled {
        background-color: #fdecec;
        color: #9b2226;
    }

    .status-badge.pending {
        background-color: #fff4d7;
        color: #9a5a00;
    }

    .info-table {
        width: 100%;
        margin-top: 4px;
        border-collapse: collapse;
    }

    .info-table td {
        width: 50%;
        padding: 6px 10px;
        border: 1px solid #e3e8f0;
        vertical-align: top;
    }

    .info-label {
        display: block;
        margin: 0 0 2px;
        color: #667085;
        font-size: 8.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .info-value {
        display: block;
        color: #172033;
        font-size: 11px;
        font-weight: 700;
    }

    .section {
        margin-top: 16px;
    }

    .section-heading {
        margin: 0 0 6px;
        padding-bottom: 4px;
        border-bottom: 2px solid #0f62fe;
        color: #173f8a;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .section-box {
        padding: 10px 12px;
        border: 1px solid #e3e8f0;
        border-radius: 4px;
        background-color: #f7f9fc;
        color: #27344d;
        font-size: 11px;
    }

    .materials-table {
        width: 100%;
        margin-top: 2px;
        border-collapse: collapse;
    }

    .materials-table th {
        padding: 6px 8px;
        border: 1px solid #e3e8f0;
        background-color: #173f8a;
        color: #ffffff;
        font-size: 9px;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
    }

    .materials-table td {
        padding: 6px 8px;
        border: 1px solid #e3e8f0;
        font-size: 10.5px;
    }

    .materials-table tr:nth-child(even) td {
        background-color: #f7f9fc;
    }

    .signature-table {
        width: 100%;
        margin-top: 4px;
        border-collapse: collapse;
    }

    .signature-table td {
        width: 50%;
        padding: 10px 12px;
        border: 1px solid #e3e8f0;
        vertical-align: top;
    }

    .signature-image {
        display: block;
        max-width: 220px;
        max-height: 90px;
        margin-top: 4px;
    }

    .no-signature {
        color: #98a2b3;
        font-style: italic;
    }

    .photo-grid {
        width: 100%;
        margin-top: 4px;
        border-collapse: collapse;
    }

    .photo-grid td {
        width: 33.33%;
        padding: 4px;
        text-align: center;
    }

    .photo-grid img {
        width: 100%;
        max-height: 130px;
        border: 1px solid #d7deea;
        border-radius: 3px;
    }
</style>
</head>
<body>

    <div class="header-rule"></div>
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Marpel">
                    @endif
                </td>
                <td class="brand-cell">
                    <p class="brand-name">Automatismos Marpel</p>
                    <p class="brand-sub">automatismosmarpel.com</p>
                </td>
                <td class="doc-cell">
                    <p class="doc-title">Parte de trabajo</p>
                    <p class="doc-number">Nº {{ str_pad((string) $workOrder->id, 6, '0', STR_PAD_LEFT) }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td>Automatismos Marpel &middot; automatismosmarpel.com</td>
                <td class="footer-right">Generado el {{ $generatedAt }} &middot; Parte #{{ $workOrder->id }}</td>
            </tr>
        </table>
    </div>

    <p style="margin: 0 0 10px;">
        <span class="status-badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
    </p>

    <table class="info-table">
        <tr>
            <td>
                <span class="info-label">Cliente</span>
                <span class="info-value">{{ $workOrder->customer?->legal_name ?: '-' }}</span>
            </td>
            <td>
                <span class="info-label">Instalacion</span>
                <span class="info-value">{{ $workOrder->installation?->name ?: '-' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="info-label">Direccion</span>
                <span class="info-value">{{ $workOrder->installation?->address ?: '-' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-label">Equipo</span>
                <span class="info-value">{{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?: '-' }}</span>
            </td>
            <td>
                <span class="info-label">Tecnico</span>
                <span class="info-value">{{ $workOrder->technician?->name ?: '-' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-label">Fecha inicio</span>
                <span class="info-value">{{ $startedAt }}</span>
            </td>
            <td>
                <span class="info-label">Fecha fin</span>
                <span class="info-value">{{ $finishedAt }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="info-label">Origen</span>
                <span class="info-value">{{ $workOrder->origin_label ?: '-' }}</span>
            </td>
            <td>
                <span class="info-label">Resultado</span>
                <span class="info-value">{{ $statusLabel }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2 class="section-heading">Descripcion del aviso</h2>
        <div class="section-box">{{ $noticeDescription ?: 'Sin descripcion.' }}</div>
    </div>

    <div class="section">
        <h2 class="section-heading">Trabajo realizado</h2>
        <div class="section-box">{{ $workOrder->work_performed ?: 'Sin descripcion.' }}</div>
    </div>

    @if ($workOrder->observations)
        <div class="section">
            <h2 class="section-heading">Observaciones</h2>
            <div class="section-box">{{ $workOrder->observations }}</div>
        </div>
    @endif

    @if ($materialRows->isNotEmpty())
        <div class="section">
            <h2 class="section-heading">Materiales utilizados</h2>
            <table class="materials-table">
                <thead>
                    <tr>
                        <th>Material</th>
                        <th>Descripcion</th>
                        <th style="text-align: right;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($materialRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['description'] }}</td>
                            <td style="text-align: right;">{{ $row['quantity'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section">
        <h2 class="section-heading">Firma del cliente</h2>
        <table class="signature-table">
            <tr>
                <td>
                    <span class="info-label">Firmante</span>
                    <span class="info-value">{{ $workOrder->customer_name ?: '-' }}</span>
                    <span class="info-label" style="margin-top: 8px;">Fecha de firma</span>
                    <span class="info-value">{{ $signedAt }}</span>
                </td>
                <td>
                    <span class="info-label">Firma</span>
                    @if ($signatureDataUri)
                        <img class="signature-image" src="{{ $signatureDataUri }}" alt="Firma cliente">
                    @else
                        <p class="no-signature">Sin firma registrada.</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if ($photoDataUris->isNotEmpty())
        <div class="section">
            <h2 class="section-heading">Fotografias</h2>
            <table class="photo-grid">
                @foreach ($photoDataUris->chunk(3) as $chunk)
                    <tr>
                        @foreach ($chunk as $photo)
                            <td><img src="{{ $photo }}" alt="Foto del parte"></td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

</body>
</html>
