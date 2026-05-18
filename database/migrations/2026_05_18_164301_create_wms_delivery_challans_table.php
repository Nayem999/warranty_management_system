<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_delivery_challans', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->foreignId('customer_id')->constrained('wms_customers')->onDelete('cascade');
            $table->foreignId('service_center_id')->constrained('wms_service_centers')->onDelete('cascade');
            $table->foreignId('courier_out_id')->nullable()->constrained('wms_couriers')->onDelete('set null');
            $table->string('courier_slip_outward')->nullable();
            $table->dateTime('delivered_date_time')->nullable();
            $table->text('delivered_remarks')->nullable();
            $table->json('claim_ids');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_delivery_challans');
    }
};
