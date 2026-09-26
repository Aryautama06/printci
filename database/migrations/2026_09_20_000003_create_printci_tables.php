<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->string('segment')->default('Regular');
            $table->timestamps();
        });

        Schema::create('payment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color', 20)->default('#818cf8');
            $table->string('icon', 40)->default('wallet');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('receipt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('paper_size', 10)->default('58mm');
            $table->string('font_size', 10)->default('normal');
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('elements')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bluetooth_id')->nullable()->unique();
            $table->string('connection_type')->default('bluetooth');
            $table->string('status')->default('offline');
            $table->string('paper_size', 10)->default('58mm');
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('receipt_template_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('destination_bank')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('proof_path')->nullable();
            $table->json('ocr_data')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('printer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->unsignedInteger('copies')->default(1);
            $table->text('error_message')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_logs');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('printers');
        Schema::dropIfExists('receipt_templates');
        Schema::dropIfExists('payment_categories');
        Schema::dropIfExists('customers');
    }
};
