<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->text('job_remarks')->nullable()->after('job_type');
            $table->string('accessories', 500)->nullable()->after('job_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->dropColumn(['job_remarks', 'accessories']);
        });
    }
};