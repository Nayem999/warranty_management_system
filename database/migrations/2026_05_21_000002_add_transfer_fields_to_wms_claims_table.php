<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE wms_claims MODIFY claim_date DATE NULL');
        DB::statement('ALTER TABLE wms_claims ADD COLUMN transferred_from_service_center_id BIGINT UNSIGNED NULL AFTER view_count');
        DB::statement('ALTER TABLE wms_claims ADD COLUMN transferred_at DATETIME NULL AFTER transferred_from_service_center_id');
        DB::statement('ALTER TABLE wms_claims ADD COLUMN transfer_reason TEXT NULL AFTER transferred_at');
        DB::statement('ALTER TABLE wms_claims ADD CONSTRAINT wms_claims_transferred_from_service_center_id_foreign FOREIGN KEY (transferred_from_service_center_id) REFERENCES wms_service_centers(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE wms_claims DROP FOREIGN KEY wms_claims_transferred_from_service_center_id_foreign');
        DB::statement('ALTER TABLE wms_claims DROP COLUMN transfer_reason');
        DB::statement('ALTER TABLE wms_claims DROP COLUMN transferred_at');
        DB::statement('ALTER TABLE wms_claims DROP COLUMN transferred_from_service_center_id');
    }
};