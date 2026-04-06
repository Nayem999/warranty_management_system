<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_service_centers', function (Blueprint $table) {
            $table->json('brand_ids')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('wms_service_centers', function (Blueprint $table) {
            $table->dropColumn('brand_ids');
        });
    }
};
