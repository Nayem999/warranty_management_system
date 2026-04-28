<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->foreignId('service_center_id')->nullable()->after('product_id')->constrained('wms_service_centers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropForeign(['service_center_id']);
            $table->dropColumn('service_center_id');
        });
    }
};