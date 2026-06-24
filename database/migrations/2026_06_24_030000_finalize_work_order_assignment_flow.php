<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        DB::table('work_orders')
            ->where('status', 'open')
            ->update(['status' => 'new']);

        DB::table('notices')
            ->where('status', 'closed')
            ->update(['status' => 'resolved']);

        DB::statement("
            UPDATE work_orders
            SET notice_id = NULL
            WHERE id IN (
                SELECT id
                FROM (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY notice_id ORDER BY id) AS row_number
                    FROM work_orders
                    WHERE notice_id IS NOT NULL
                ) duplicated_notice_orders
                WHERE duplicated_notice_orders.row_number > 1
            )
        ");

        DB::statement("
            UPDATE work_orders
            SET review_id = NULL
            WHERE id IN (
                SELECT id
                FROM (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY review_id ORDER BY id) AS row_number
                    FROM work_orders
                    WHERE review_id IS NOT NULL
                ) duplicated_review_orders
                WHERE duplicated_review_orders.row_number > 1
            )
        ");

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS work_orders_notice_id_unique ON work_orders (notice_id) WHERE notice_id IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS work_orders_review_id_unique ON work_orders (review_id) WHERE review_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS work_orders_notice_id_unique');
        DB::statement('DROP INDEX IF EXISTS work_orders_review_id_unique');

        DB::table('work_orders')
            ->where('status', 'new')
            ->update(['status' => 'open']);

        DB::table('notices')
            ->where('status', 'resolved')
            ->update(['status' => 'closed']);
    }
};
