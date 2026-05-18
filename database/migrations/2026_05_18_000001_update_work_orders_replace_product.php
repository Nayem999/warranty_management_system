<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn(['replace_product_name', 'replace_product_info']);
            $table->foreignId('replace_product_id')->nullable()->constrained('wms_products')->onDelete('set null')->after('replace_serial');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropForeign(['replace_product_id']);
            $table->dropColumn('replace_product_id');
            $table->string('replace_product_name')->nullable()->after('replace_serial');
            $table->text('replace_product_info')->nullable()->after('replace_product_name');
        });
    }
};