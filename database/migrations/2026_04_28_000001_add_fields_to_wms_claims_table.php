<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->foreignId('engineer_id')->nullable()->after('service_center_id')->constrained('users')->onDelete('set null');
            $table->foreignId('courier_in_id')->nullable()->after('engineer_id')->constrained('wms_couriers')->onDelete('set null');
            $table->string('courier_slip_inward')->nullable()->after('courier_in_id');
            $table->foreignId('courier_out_id')->nullable()->after('courier_slip_inward')->constrained('wms_couriers')->onDelete('set null');
            $table->string('courier_slip_outward')->nullable()->after('courier_out_id');
            $table->dateTime('received_date_time')->nullable()->after('courier_slip_outward');
            $table->dateTime('delivered_date_time')->nullable()->after('received_date_time');
            $table->integer('counter')->default(0)->after('delivered_date_time');
            $table->date('wo_assigned_date')->nullable()->after('counter');
            $table->date('wo_closed_date')->nullable()->after('wo_assigned_date');
            $table->date('wo_delivery_date')->nullable()->after('wo_closed_date');
            $table->integer('tat')->nullable()->after('wo_delivery_date');
            $table->boolean('doa')->default(false)->after('tat');
            $table->string('replace_serial')->nullable()->after('doa');
            $table->string('replace_product_name')->nullable()->after('replace_serial');
            $table->text('replace_product_info')->nullable()->after('replace_product_name');
            $table->string('replace_ref')->nullable()->after('replace_product_info');
            $table->string('invoice_no')->nullable()->after('replace_ref');
            $table->date('invoice_date')->nullable()->after('invoice_no');
            $table->decimal('purchase_price', 10, 2)->nullable()->after('invoice_date');
            $table->string('ref')->nullable()->after('purchase_price');
            $table->date('web_wty_date')->nullable()->after('ref');
            $table->text('additional_comment')->nullable()->after('web_wty_date');
            $table->text('work_done_comment')->nullable()->after('additional_comment');
            $table->text('customer_feedback')->nullable()->after('work_done_comment');
            $table->integer('customer_rating')->nullable()->after('customer_feedback');
            $table->string('feedback_token')->nullable()->after('customer_rating');
            $table->text('status_comment')->nullable()->after('feedback_token');
            $table->enum('service_type', ['In Warranty', 'Warranty Void', 'DOA', 'OOW/Expired'])->nullable()->after('status_comment');
            $table->enum('job_type', ['Carry In', 'On Site', 'Pick Up'])->nullable()->after('service_type');
            $table->foreignId('assigned_by')->nullable()->after('job_type')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $columnsToDrop = [
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
            $table->dropForeign(['engineer_id']);
            $table->dropForeign(['courier_in_id']);
            $table->dropForeign(['courier_out_id']);
            $table->dropForeign(['assigned_by']);
            $table->dropColumn($columnsToDrop);
        });
    }
};