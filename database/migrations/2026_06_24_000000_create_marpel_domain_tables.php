<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('tax_id')->nullable()->index();
            $table->string('fiscal_address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('primary_contact_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('installations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('access_hours')->nullable();
            $table->text('access_instructions')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->index(['customer_id', 'name']);
        });

        Schema::create('equipment_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('default_revision_interval_days')->default(180);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('equipment', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->nullable()->index();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->index();
            $table->string('internal_location')->nullable();
            $table->date('installed_at')->nullable();
            $table->date('last_review_at')->nullable();
            $table->date('next_review_at')->nullable()->index();
            $table->unsignedInteger('revision_interval_days')->default(180);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['installation_id', 'status']);
        });

        Schema::create('materials', function (Blueprint $table): void {
            $table->id();
            $table->string('sku')->nullable()->unique();
            $table->string('name');
            $table->string('unit')->default('ud');
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('type')->default('maintenance');
            $table->string('status')->default('active')->index();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('billing_period')->default('monthly');
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->boolean('includes_emergency_service')->default(false);
            $table->boolean('includes_preventive_maintenance')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'status']);
        });

        Schema::create('contract_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->unsignedInteger('revision_interval_days')->nullable();
            $table->timestamps();
        });

        Schema::create('notices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reported_by')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('channel')->default('phone');
            $table->string('priority')->default('normal')->index();
            $table->string('status')->default('pending')->index();
            $table->text('description');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->boolean('requires_quote')->default(false);
            $table->timestamps();
            $table->index(['installation_id', 'status']);
            $table->index(['assigned_user_id', 'scheduled_at']);
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at')->index();
            $table->dateTime('performed_at')->nullable();
            $table->string('type')->default('preventive');
            $table->string('status')->default('scheduled')->index();
            $table->string('result')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_review_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_user_id', 'scheduled_at']);
        });

        Schema::create('quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('review_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('draft')->index();
            $table->date('valid_until')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->foreignId('notice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('review_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open')->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->string('result')->nullable();
            $table->text('work_performed')->nullable();
            $table->text('observations')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_signature_path')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_user_id', 'status']);
            $table->index(['installation_id', 'status']);
        });

        Schema::create('work_order_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('work_order_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->string('kind')->default('general');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->string('status')->default('draft')->index();
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('work_order_photos');
        Schema::dropIfExists('work_order_materials');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('notices');
        Schema::dropIfExists('contract_lines');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_types');
        Schema::dropIfExists('installations');
        Schema::dropIfExists('customers');
    }
};
