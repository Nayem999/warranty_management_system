<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_user_brand_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('brand_id')->constrained('wms_brands')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['user_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_user_brand_access');
    }
};
