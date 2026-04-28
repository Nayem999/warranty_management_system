<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->dropColumn([
                'replace_serial',
                'replace_product_name',
                'replace_product_info',
                'replace_ref',
            ]);
        });

        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->string('replace_serial')->nullable()->after('product_id');
            $table->string('replace_product_name')->nullable()->after('replace_serial');
            $table->text('replace_product_info')->nullable()->after('replace_product_name');
            $table->string('replace_ref')->nullable()->after('replace_product_info');
        });
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->string('replace_serial')->nullable();
            $table->string('replace_product_name')->nullable();
            $table->text('replace_product_info')->nullable();
            $table->string('replace_ref')->nullable();
        });

        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'replace_serial',
                'replace_product_name',
                'replace_product_info',
                'replace_ref',
            ]);
        });
    }
};