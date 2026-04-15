<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->text('status_comment')->nullable()->after('status');
            $table->string('replace_ref')->nullable()->after('replace_serial');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn(['status_comment', 'replace_ref']);
        });
    }
};
