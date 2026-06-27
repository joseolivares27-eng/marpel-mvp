<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class WorkOrder extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $workOrder): void {
            if (! $workOrder->folio_number) {
                $maxFolio = static::max('folio_number');
                $workOrder->folio_number = $maxFolio ? $maxFolio + 1 : 432;
            }
        });

        static::saving(function (WorkOrder $workOrder): void {
            app(\App\Services\OperationalContextValidator::class)->validate($workOrder);

            if ($workOrder->status !== null) {
                $workOrder->status = $workOrder->normalizeStatusForStorage($workOrder->status);
            }

            if ($workOrder->result !== null) {
                $workOrder->result = $workOrder->normalizeResultForStorage($workOrder->result);
            }

            $workOrder->prepareClosingData();
            $workOrder->validateClosingData();
        });

        static::saved(function (WorkOrder $workOrder): void {
            if ($workOrder->status !== 'closed') {
                return;
            }

            if (! $workOrder->shouldFinalizeClosedWorkOrder()) {
                return;
            }

            app(\App\Services\WorkOrderClosureFinalizer::class)->finalize($workOrder);
        });

        static::deleting(function (WorkOrder $workOrder): void {
            if (! $workOrder->notice_id) {
                return;
            }

            $notice = $workOrder->notice()->first();

            if ($notice) {
                Notice::withoutEvents(fn () => $notice->delete());
            }
        });
    }

    protected $fillable = [
        'folio_number',
        'customer_id',
        'installation_id',
        'equipment_id',
        'notice_id',
        'review_id',
        'quote_id',
        'assigned_user_id',
        'status',
        'started_at',
        'finished_at',
        'result',
        'work_performed',
        'observations',
        'customer_name',
        'customer_signature_path',
        'signed_at',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(WorkOrderMaterial::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function getFolioLabelAttribute(): string
    {
        $year = $this->created_at?->year ?? now()->year;

        return "{$year}/{$this->folio_number}";
    }

    public function getOriginLabelAttribute(): string
    {
        if ($this->notice_id) {
            return 'Aviso';
        }

        if ($this->review_id) {
            return 'Revision';
        }

        return 'Manual';
    }

    private function validateClosingData(): void
    {
        if ($this->status !== 'closed') {
            return;
        }

        if (! $this->started_at) {
            throw ValidationException::withMessages([
                'started_at' => 'Indica fecha de inicio.',
            ]);
        }

        if (! in_array($this->result, ['solved', 'unresolved', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'result' => 'Para cerrar el parte debes indicar resultado: Solucionado, No solucionado o Anulado.',
            ]);
        }

        if (! $this->finished_at) {
            throw ValidationException::withMessages([
                'finished_at' => 'Indica fecha de fin o se asignara automaticamente.',
            ]);
        }

        if (trim((string) $this->work_performed) === '') {
            throw ValidationException::withMessages([
                'work_performed' => 'Indica el trabajo realizado.',
            ]);
        }

        if ($this->result === 'solved' && ! $this->customer_name) {
            throw ValidationException::withMessages([
                'customer_name' => 'Indica el nombre del firmante.',
            ]);
        }

        if ($this->result === 'solved' && ! $this->customer_signature_path) {
            throw ValidationException::withMessages([
                'customer_signature_path' => 'El cliente debe firmar para cerrar como solucionado.',
            ]);
        }
    }

    private function prepareClosingData(): void
    {
        if ($this->status !== 'closed') {
            return;
        }

        if (! $this->finished_at) {
            $this->finished_at = now();
        }

        if ($this->result === 'solved' && $this->customer_signature_path) {
            $this->signed_at = $this->finished_at;
        }
    }

    private function normalizeStatusForStorage(string $status): string
    {
        return match ($status) {
            'closed', 'cerrado', 'cancelled', 'resolved', 'completed' => 'closed',
            default => 'open',
        };
    }

    private function normalizeResultForStorage(string $result): string
    {
        return match ($result) {
            'solved', 'solucionado', 'ok' => 'solved',
            'not_solved', 'unresolved', 'no_solucionado', 'not_located', 'incident' => 'unresolved',
            'cancelled', 'anulado' => 'cancelled',
            default => $result,
        };
    }

    private function shouldFinalizeClosedWorkOrder(): bool
    {
        if (! $this->pdf_path) {
            return true;
        }

        return $this->wasChanged([
            'status',
            'result',
            'started_at',
            'finished_at',
            'work_performed',
            'observations',
            'customer_name',
            'customer_signature_path',
            'signed_at',
        ]);
    }
}
