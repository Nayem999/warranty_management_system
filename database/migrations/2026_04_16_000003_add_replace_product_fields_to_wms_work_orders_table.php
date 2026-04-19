<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function ($table) {
            $table->string('replace_product_name', 255)->nullable()->after('replace_serial');
            $table->text('replace_product_info')->nullable()->after('replace_product_name');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function ($table) {
            $table->dropColumn(['replace_product_name', 'replace_product_info']);
        });
    }
};
