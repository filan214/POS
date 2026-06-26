<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total')->default(0);
            $table->enum('payment_method', ['cash', 'qris', 'debit'])->default('cash');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->integer('change_amount')->default(0);
            $table->enum('status', ['completed', 'voided'])->default('completed')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
