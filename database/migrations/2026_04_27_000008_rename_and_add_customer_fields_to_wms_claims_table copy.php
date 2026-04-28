<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE wms_claims DROP COLUMN customer_firstname');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE wms_claims DROP COLUMN customer_lastname');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE wms_claims DROP COLUMN customer_email');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE wms_claims DROP COLUMN customer_phone');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE wms_claims DROP COLUMN customer_city');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE wms_claims DROP COLUMN customer_address');
        } catch (\Exception $e) {}

        try {
            DB::statement('ALTER TABLE wms_claims MODIFY customer_id BIGINT UNSIGNED NULL');
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->string('customer_firstname')->nullable();
            $table->string('customer_lastname')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_city')->nullable();
            $table->text('customer_address')->nullable();

            $table->dropForeign(['customer_id']);
        });
    }
};