<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_work_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('wms_work_orders')->onDelete('cascade');
            $table->foreignId('part_id')->nullable()->constrained('wms_parts')->onDelete('set null');
            $table->string('part_code')->nullable();
            $table->text('part_description')->nullable();
            $table->string('case_id')->nullable();
            $table->timestamp('case_date_time')->nullable();
            $table->string('order_id')->nullable();
            $table->timestamp('order_date_time')->nullable();
            $table->timestamp('received_date_time')->nullable();
            $table->timestamp('install_date_time')->nullable();
            $table->string('good_part_serial')->nullable();
            $table->string('faulty_part_serial')->nullable();
            $table->timestamp('return_date_time')->nullable();
            $table->enum('part_returned', ['Yes', 'No'])->nullable();
            $table->enum('part_status', ['DOA Part', 'Wrong Parts delivered', 'Cancelled/Roll Over', 'Used in repair', 'Un-used', 'Damaged'])->nullable();
            $table->text('part_return_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_work_order_parts');
    }
};
