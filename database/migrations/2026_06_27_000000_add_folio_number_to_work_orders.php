<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->unsignedInteger('folio_number')->nullable()->unique()->after('id');
        });

        $folio = 432;

        DB::table('work_orders')
            ->orderBy('id')
            ->select('id')
            ->each(function (object $workOrder) use (&$folio): void {
                DB::table('work_orders')->where('id', $workOrder->id)->update(['folio_number' => $folio]);
                $folio++;
            });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table): void {
            $table->dropColumn('folio_number');
        });
    }
};
