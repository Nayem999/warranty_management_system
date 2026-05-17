<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_service_centers_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_center_id')->constrained('wms_service_centers')->onDelete('cascade');
            $table->foreignId('brand_id')->constrained('wms_brands')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['service_center_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_service_centers_brands');
    }
};