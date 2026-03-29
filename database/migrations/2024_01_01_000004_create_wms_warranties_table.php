<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_warranties', function (Blueprint $table) {
            $table->id();
            $table->string('product_serial')->unique();
            $table->string('product_name');
            $table->text('product_info')->nullable();
            $table->foreignId('brand_id')->constrained('wms_brands')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('wms_product_categories')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('is_void', ['YES', 'NO'])->default('NO');
            $table->text('void_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_warranties');
    }
};
