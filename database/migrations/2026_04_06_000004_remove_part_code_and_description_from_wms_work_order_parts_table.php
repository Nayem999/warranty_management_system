<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_order_parts', function (Blueprint $table) {
            $table->dropColumn(['part_code', 'part_description']);
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_order_parts', function (Blueprint $table) {
            $table->string('part_code')->nullable();
            $table->text('part_description')->nullable();
        });
    }
};
