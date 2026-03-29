<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_service_centers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('address')->nullable();
            $table->string('uan')->nullable();
            $table->string('email')->nullable();
            $table->string('working_hours')->nullable();
            $table->string('logo')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_service_centers');
    }
};
