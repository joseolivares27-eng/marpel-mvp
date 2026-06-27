<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('notion_page_id')->nullable()->unique()->after('id');
            $table->string('drive_folder_url')->nullable()->after('notes');
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->string('notion_page_id')->nullable()->unique()->after('id');
            $table->string('drive_folder_url')->nullable()->after('notes');
        });

        Schema::table('notices', function (Blueprint $table): void {
            $table->string('notion_page_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn(['notion_page_id', 'drive_folder_url']);
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn(['notion_page_id', 'drive_folder_url']);
        });

        Schema::table('notices', function (Blueprint $table): void {
            $table->dropColumn('notion_page_id');
        });
    }
};
