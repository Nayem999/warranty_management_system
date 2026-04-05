<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_product_categories', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('id')->constrained('wms_brands')->onDelete('cascade');
        });

        $defaultBrandId = DB::table('wms_brands')->first()?->id ?? 1;
        DB::table('wms_product_categories')->whereNull('brand_id')->update(['brand_id' => $defaultBrandId]);

        Schema::table('wms_product_categories', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('wms_product_categories', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->change();
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
    }
};
