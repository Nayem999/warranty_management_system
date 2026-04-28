<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $columnsToDrop = [
                'service_center_id',
                'engineer_id',
                'courier_in_id',
                'courier_slip_inward',
                'courier_out_id',
                'courier_slip_outward',
                'received_date_time',
                'delivered_date_time',
                'counter',
                'wo_assigned_date',
                'wo_closed_date',
                'wo_delivery_date',
                'tat',
                'doa',
                'replace_serial',
                'replace_product_name',
                'replace_product_info',
                'replace_ref',
                'invoice_no',
                'invoice_date',
                'purchase_price',
                'ref',
                'web_wty_date',
                'additional_comment',
                'work_done_comment',
                'customer_feedback',
                'customer_rating',
                'feedback_token',
                'status_comment',
                'service_type',
                'job_type',
                'assigned_by',
            ];

            $table->dropForeign(['service_center_id']);
            $table->dropForeign(['engineer_id']);
            $table->dropForeign(['courier_in_id']);
            $table->dropForeign(['courier_out_id']);
            $table->dropForeign(['assigned_by']);
            
            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->foreignId('service_center_id')->nullable()->constrained('wms_service_centers')->onDelete('set null');
            $table->foreignId('engineer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('courier_in_id')->nullable()->constrained('wms_couriers')->onDelete('set null');
            $table->string('courier_slip_inward')->nullable();
            $table->foreignId('courier_out_id')->nullable()->constrained('wms_couriers')->onDelete('set null');
            $table->string('courier_slip_outward')->nullable();
            $table->dateTime('received_date_time')->nullable();
            $table->dateTime('delivered_date_time')->nullable();
            $table->integer('counter')->default(0);
            $table->date('wo_assigned_date')->nullable();
            $table->date('wo_closed_date')->nullable();
            $table->date('wo_delivery_date')->nullable();
            $table->integer('tat')->nullable();
            $table->boolean('doa')->default(false);
            $table->string('replace_serial')->nullable();
            $table->string('replace_product_name')->nullable();
            $table->text('replace_product_info')->nullable();
            $table->string('replace_ref')->nullable();
            $table->string('invoice_no')->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('ref')->nullable();
            $table->date('web_wty_date')->nullable();
            $table->text('additional_comment')->nullable();
            $table->text('work_done_comment')->nullable();
            $table->text('customer_feedback')->nullable();
            $table->integer('customer_rating')->nullable();
            $table->string('feedback_token')->nullable();
            $table->text('status_comment')->nullable();
            $table->enum('service_type', ['In Warranty', 'Warranty Void', 'DOA', 'OOW/Expired'])->nullable();
            $table->enum('job_type', ['Carry In', 'On Site', 'Pick Up'])->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};