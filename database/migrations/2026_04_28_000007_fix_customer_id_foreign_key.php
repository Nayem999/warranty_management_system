<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE wms_claims DROP FOREIGN KEY wms_claims_customer_user_id_foreign');
        DB::statement('ALTER TABLE wms_claims ADD CONSTRAINT wms_claims_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES wms_customers(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE wms_claims DROP FOREIGN KEY wms_claims_customer_id_foreign');
        DB::statement('ALTER TABLE wms_claims ADD CONSTRAINT wms_claims_customer_user_id_foreign FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL');
    }
};