<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_products', function (Blueprint $table) {
            $table->id();
            $table->string('model_no')->unique();
            $table->string('serial_number')->nullable();
            $table->text('item_description')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained('wms_brands')->onDelete('set null');
            $table->foreignId('category_id')->nullable()->constrained('wms_product_categories')->onDelete('set null');
            $table->foreignId('sub_category_id')->nullable()->constrained('wms_product_categories')->onDelete('set null');
            $table->boolean('is_countable')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_products');
    }
};