<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('phone2')->nullable()->after('phone');
            $table->string('city')->nullable()->after('fiscal_address');
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('province');
            $table->string('iban')->nullable()->after('postal_code');
            $table->string('client_type')->nullable()->after('iban');
            $table->date('contract_start_date')->nullable()->after('client_type');
            $table->decimal('monthly_amount', 10, 2)->nullable()->after('contract_start_date');
            $table->unsignedInteger('equipment_count')->nullable()->after('monthly_amount');
            $table->text('equipment_description')->nullable()->after('equipment_count');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn([
                'phone2',
                'city',
                'province',
                'postal_code',
                'iban',
                'client_type',
                'contract_start_date',
                'monthly_amount',
                'equipment_count',
                'equipment_description',
            ]);
        });
    }
};
