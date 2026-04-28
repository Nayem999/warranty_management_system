<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_order_parts', function (Blueprint $table) {
            $table->foreignId('claim_id')->nullable()->after('id')->onDelete('cascade');
            $table->dateTime('claim_date_time')->nullable()->after('claim_id');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_order_parts', function (Blueprint $table) {
            $table->dropForeign(['claim_id']);
            $table->dropColumn(['claim_id', 'claim_date_time']);
        });
    }
};
