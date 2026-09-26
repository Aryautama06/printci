<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('admin_fee', 15, 2)->default(2500)->after('amount');
            $table->decimal('total_amount', 15, 2)->default(0)->after('admin_fee');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['admin_fee', 'total_amount']);
        });
    }
};
