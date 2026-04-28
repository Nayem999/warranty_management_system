<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE wms_work_order_parts DROP FOREIGN KEY wms_work_order_parts_claim_id_foreign');
        DB::statement('ALTER TABLE wms_work_order_parts MODIFY claim_id VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE wms_work_order_parts MODIFY claim_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE wms_work_order_parts ADD CONSTRAINT wms_work_order_parts_claim_id_foreign FOREIGN KEY (claim_id) REFERENCES wms_claims(id) ON DELETE CASCADE');
    }
};