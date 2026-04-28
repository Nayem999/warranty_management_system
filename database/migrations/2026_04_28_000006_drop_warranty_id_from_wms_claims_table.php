<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE wms_claims DROP FOREIGN KEY wms_claims_product_serial_foreign');
        DB::statement('ALTER TABLE wms_claims DROP COLUMN warranty_id');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE wms_claims ADD COLUMN warranty_id BIGINT UNSIGNED NOT NULL AFTER product_id');
        DB::statement('ALTER TABLE wms_claims ADD CONSTRAINT wms_claims_product_serial_foreign FOREIGN KEY (warranty_id) REFERENCES wms_warranties(id) ON DELETE CASCADE');
    }
};