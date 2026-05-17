<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_order_parts', function (Blueprint $table) {
            $table->string('labour_claim_id', 100)->nullable()->after('part_return_comment');
            $table->date('labour_claim_date')->nullable()->after('labour_claim_id');
            $table->foreignId('faulty_part_id')->nullable()->constrained('wms_parts')->onDelete('set null')->after('labour_claim_date');
            $table->text('faulty_description')->nullable()->after('faulty_part_id');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_order_parts', function (Blueprint $table) {
            $table->dropForeign(['faulty_part_id']);
            $table->dropColumn(['labour_claim_id', 'labour_claim_date', 'faulty_part_id', 'faulty_description']);
        });
    }
};