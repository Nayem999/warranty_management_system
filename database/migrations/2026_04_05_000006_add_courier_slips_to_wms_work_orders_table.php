<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->text('courier_slip_inward')->nullable()->after('courier_in_id');
            $table->text('courier_slip_outward')->nullable()->after('courier_out_id');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn(['courier_slip_inward', 'courier_slip_outward']);
        });
    }
};
