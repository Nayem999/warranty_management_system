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
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->renameColumn('product_serial', 'warranty_id');
        });
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->renameColumn('warranty_id', 'product_serial');
        });
    }
};
