<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_brands', function ($table) {
            $table->unique('short_name', 'wms_brands_short_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('wms_brands', function ($table) {
            $table->dropUnique('wms_brands_short_name_unique');
        });
    }
};
