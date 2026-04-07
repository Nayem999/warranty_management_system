<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn(['part1_used', 'part2_used', 'part3_used']);
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->string('part1_used')->nullable();
            $table->string('part2_used')->nullable();
            $table->string('part3_used')->nullable();
        });
    }
};
