<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('wms_brands')->onDelete('cascade');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_product_categories');
    }
};
