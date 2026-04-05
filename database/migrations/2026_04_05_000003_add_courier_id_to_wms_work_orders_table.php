<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            if (Schema::hasColumn('wms_work_orders', 'courier_id')) {
                $table->dropForeign(['courier_id']);
                $table->dropColumn('courier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->foreignId('courier_id')->nullable()->after('service_center_id')->constrained('wms_couriers')->onDelete('set null');
        });
    }
};
