<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wms_product_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('wms_product_categories')->onDelete('cascade');
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
    }

    public function down(): void
    {
        Schema::table('wms_product_categories', function (Blueprint $table) {
            $table->foreignId('brand_id')->constrained('wms_brands')->onDelete('cascade');
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
