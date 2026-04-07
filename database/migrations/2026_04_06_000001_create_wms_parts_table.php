<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('wms_brands')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('wms_product_categories')->onDelete('set null');
            $table->foreignId('sub_category_id')->nullable()->constrained('wms_product_categories')->onDelete('set null');
            $table->string('part_id')->unique();
            $table->text('part_description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_parts');
    }
};
