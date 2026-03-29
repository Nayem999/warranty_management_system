<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_name')->unique();
            $table->mediumText('setting_value')->nullable();
            $table->string('type')->default('app');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_settings');
    }
};
