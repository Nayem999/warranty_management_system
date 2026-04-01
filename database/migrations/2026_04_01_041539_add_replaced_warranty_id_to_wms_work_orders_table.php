<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->foreignId('replaced_warranty_id')->nullable()->after('replace_serial')->constrained('wms_warranties')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropForeign(['replaced_warranty_id']);
            $table->dropColumn('replaced_warranty_id');
        });
    }
};
