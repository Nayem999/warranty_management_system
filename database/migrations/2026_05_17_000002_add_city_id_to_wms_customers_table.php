<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_customers', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->constrained('wms_cities')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('wms_customers', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });
    }
};