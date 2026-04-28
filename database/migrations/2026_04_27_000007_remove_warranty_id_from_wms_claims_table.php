<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE wms_claims DROP COLUMN warranty_id');
        } catch (\Exception $e) {
            // Column may already be dropped or doesn't exist
        }
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->foreignId('warranty_id')->nullable()->constrained('wms_warranties')->onDelete('cascade');
        });
    }
};