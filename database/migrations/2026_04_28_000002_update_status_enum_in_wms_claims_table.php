<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wms_claims MODIFY COLUMN status ENUM('Not Assigned', 'Open', 'In Progress', 'Closed(Repaired)', 'Closed-(Without Repaired)', 'Closed-(Replaced)', 'Closed-(Reimbursed)', 'Delivered') DEFAULT 'Not Assigned'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE wms_claims MODIFY COLUMN status ENUM('Open', 'Closed', 'Converted') DEFAULT 'Open'");
    }
};