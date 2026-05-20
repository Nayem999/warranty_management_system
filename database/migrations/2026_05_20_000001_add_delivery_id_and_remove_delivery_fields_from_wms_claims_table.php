<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('wms_claims', 'delivery_id')) {
                $table->foreignId('delivery_id')->nullable()->after('is_delivered')->constrained('wms_delivery_challans')->onDelete('set null');
            }
            $table->dropForeign(['courier_out_id']);
            $table->dropColumn(['courier_out_id', 'courier_slip_outward', 'delivered_date_time', 'delivered_remarks']);
        });
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->foreignId('courier_out_id')->nullable()->after('courier_slip_inward')->constrained('wms_couriers')->onDelete('set null');
            $table->string('courier_slip_outward')->nullable()->after('courier_out_id');
            $table->dateTime('delivered_date_time')->nullable()->after('courier_slip_outward');
            $table->text('delivered_remarks')->nullable()->after('delivered_date_time');
            $table->dropForeign(['delivery_id']);
            $table->dropColumn('delivery_id');
        });
    }
};