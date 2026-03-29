<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number')->unique();
            $table->foreignId('claim_id')->constrained('wms_claims')->onDelete('cascade');
            $table->foreignId('service_center_id')->nullable()->constrained('wms_service_centers')->onDelete('set null');
            $table->date('wo_assigned_date')->nullable();
            $table->date('wo_closed_date')->nullable();
            $table->date('wo_delivery_date')->nullable();
            $table->integer('tat')->nullable();
            $table->boolean('doa')->default(false);
            $table->string('replace_serial')->nullable();
            $table->text('additional_comment')->nullable();
            $table->text('work_done_comment')->nullable();
            $table->text('customer_feedback')->nullable();
            $table->integer('customer_rating')->nullable();
            $table->string('feedback_token')->unique()->nullable();
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Delivered'])->default('Pending');
            $table->string('part1_used')->nullable();
            $table->string('part2_used')->nullable();
            $table->string('part3_used')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_work_orders');
    }
};
