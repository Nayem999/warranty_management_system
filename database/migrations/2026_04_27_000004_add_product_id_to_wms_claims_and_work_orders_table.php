<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('warranty_id')->constrained('wms_products')->onDelete('set null');
        });

        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('replaced_warranty_id')->constrained('wms_products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};