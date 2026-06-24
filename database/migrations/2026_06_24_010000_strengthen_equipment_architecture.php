<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_types', function (Blueprint $table): void {
            $table->string('category')->nullable()->after('name');
            $table->string('icon')->nullable()->after('category');
            $table->string('default_revision_periodicity')->default('semiannual')->after('icon');
            $table->unsignedInteger('default_custom_revision_interval_days')->nullable()->after('default_revision_interval_days');
            $table->boolean('is_active')->default(true)->after('default_custom_revision_interval_days');
        });

        DB::table('equipment_types')->updateOrInsert(
            ['name' => 'Personalizado'],
            [
                'category' => 'Personalizado',
                'icon' => 'heroicon-o-plus-circle',
                'default_revision_periodicity' => 'custom',
                'default_revision_interval_days' => 180,
                'default_custom_revision_interval_days' => 180,
                'is_active' => true,
                'description' => 'Tipo libre para nuevos equipos o servicios sin desarrollo adicional.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Schema::table('contracts', function (Blueprint $table): void {
            $table->json('coverages')->nullable()->after('monthly_fee');
        });

        Schema::table('equipment', function (Blueprint $table): void {
            $table->string('category')->nullable()->after('name');
            $table->string('revision_periodicity')->default('semiannual')->after('next_review_at');
            $table->unsignedInteger('custom_revision_interval_days')->nullable()->after('revision_interval_days');
        });

        $nextCode = 1;

        DB::table('equipment')
            ->whereNotNull('code')
            ->where('code', 'like', 'EQ-%')
            ->orderBy('code')
            ->pluck('code')
            ->each(function (string $code) use (&$nextCode): void {
                $numeric = (int) preg_replace('/\D/', '', $code);
                $nextCode = max($nextCode, $numeric + 1);
            });

        DB::table('equipment')
            ->where(fn ($query) => $query
                ->whereNull('code')
                ->orWhere('code', '')
                ->orWhere('code', 'not like', 'EQ-%'))
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $equipment) use (&$nextCode): void {
                DB::table('equipment')
                    ->where('id', $equipment->id)
                    ->update(['code' => sprintf('EQ-%06d', $nextCode++)]);
            });

        Schema::table('equipment', function (Blueprint $table): void {
            $table->dropIndex('equipment_code_index');
            $table->unique('code');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->foreignId('equipment_id')->nullable()->after('installation_id')->constrained('equipment')->nullOnDelete();
        });

        DB::table('quotes')
            ->whereNull('equipment_id')
            ->orderBy('id')
            ->get(['id', 'notice_id', 'review_id'])
            ->each(function (object $quote): void {
                $equipmentId = null;

                if ($quote->notice_id) {
                    $equipmentId = DB::table('notices')->where('id', $quote->notice_id)->value('equipment_id');
                }

                if (! $equipmentId && $quote->review_id) {
                    $equipmentId = DB::table('reviews')->where('id', $quote->review_id)->value('equipment_id');
                }

                if ($equipmentId) {
                    DB::table('quotes')->where('id', $quote->id)->update(['equipment_id' => $equipmentId]);
                }
            });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('equipment_id')->nullable()->after('installation_id')->constrained('equipment')->nullOnDelete();
        });

        DB::table('invoices')
            ->whereNull('equipment_id')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $invoice): void {
                $equipmentId = DB::table('invoice_lines')
                    ->join('work_orders', 'invoice_lines.work_order_id', '=', 'work_orders.id')
                    ->where('invoice_lines.invoice_id', $invoice->id)
                    ->whereNotNull('work_orders.equipment_id')
                    ->value('work_orders.equipment_id');

                if ($equipmentId) {
                    DB::table('invoices')->where('id', $invoice->id)->update(['equipment_id' => $equipmentId]);
                }
            });

        Schema::create('integration_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('external');
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('integration_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction')->default('inbound');
            $table->string('event_type');
            $table->string('status')->default('pending')->index();
            $table->nullableMorphs('related');
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_events');
        Schema::dropIfExists('integration_sources');

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('equipment_id');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('equipment_id');
        });

        Schema::table('equipment', function (Blueprint $table): void {
            $table->dropUnique('equipment_code_unique');
            $table->index('code');
            $table->dropColumn(['category', 'revision_periodicity', 'custom_revision_interval_days']);
        });

        Schema::table('equipment_types', function (Blueprint $table): void {
            $table->dropColumn([
                'category',
                'icon',
                'default_revision_periodicity',
                'default_custom_revision_interval_days',
                'is_active',
            ]);
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn('coverages');
        });
    }
};
