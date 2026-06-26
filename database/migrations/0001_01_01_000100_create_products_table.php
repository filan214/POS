<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name');
            $table->string('category')->index();
            $table->unsignedInteger('cost_price')->default(0);
            $table->unsignedInteger('sell_price')->default(0);
            $table->integer('stock_qty')->default(0);
            $table->unsignedInteger('reorder_threshold')->default(0);
            $table->string('image_path')->nullable();
            $table->string('emoji', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
