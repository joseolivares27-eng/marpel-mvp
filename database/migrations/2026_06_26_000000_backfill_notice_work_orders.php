<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('notices')
            ->select([
                'id',
                'customer_id',
                'installation_id',
                'equipment_id',
                'assigned_user_id',
                'scheduled_at',
                'description',
            ])
            ->where(fn ($query) => $query
                ->whereNotNull('assigned_user_id')
                ->orWhereNotNull('scheduled_at'))
            ->whereNotIn('status', ['completed', 'resolved', 'cancelled'])
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('work_orders')
                ->whereColumn('work_orders.notice_id', 'notices.id'))
            ->orderBy('id')
            ->each(function (object $notice) use ($now): void {
                DB::table('work_orders')->insert([
                    'customer_id' => $notice->customer_id,
                    'installation_id' => $notice->installation_id,
                    'equipment_id' => $notice->equipment_id,
                    'notice_id' => $notice->id,
                    'assigned_user_id' => $notice->assigned_user_id,
                    'status' => 'open',
                    'started_at' => $notice->scheduled_at,
                    'result' => 'pending',
                    'observations' => $notice->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        $workOrderIds = DB::table('work_orders')
            ->join('notices', 'work_orders.notice_id', '=', 'notices.id')
            ->where('work_orders.status', 'open')
            ->where('work_orders.result', 'pending')
            ->whereNull('work_orders.review_id')
            ->whereNull('work_orders.quote_id')
            ->whereNull('work_orders.finished_at')
            ->whereNull('work_orders.work_performed')
            ->whereNull('work_orders.customer_name')
            ->whereNull('work_orders.customer_signature_path')
            ->whereNull('work_orders.signed_at')
            ->whereColumn('work_orders.observations', 'notices.description')
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('work_order_materials')
                ->whereColumn('work_order_materials.work_order_id', 'work_orders.id'))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('work_order_photos')
                ->whereColumn('work_order_photos.work_order_id', 'work_orders.id'))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('invoice_lines')
                ->whereColumn('invoice_lines.work_order_id', 'work_orders.id'))
            ->pluck('work_orders.id');

        if ($workOrderIds->isNotEmpty()) {
            DB::table('work_orders')->whereIn('id', $workOrderIds)->delete();
        }
    }
};
