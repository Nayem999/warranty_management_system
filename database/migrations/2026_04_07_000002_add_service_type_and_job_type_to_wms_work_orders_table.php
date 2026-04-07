<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->enum('service_type', ['dead on arrival', 'out of warranty expire', 'warranty void'])->nullable()->after('status');
            $table->enum('job_type', ['Carry In', 'On Site', 'Pick Up'])->nullable()->after('service_type');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'job_type']);
        });
    }
};
