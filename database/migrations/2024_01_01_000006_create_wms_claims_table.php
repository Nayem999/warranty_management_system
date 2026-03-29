<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('product_serial')->constrained('wms_warranties')->onDelete('cascade');
            $table->text('problem_description');
            $table->string('customer_firstname');
            $table->string('customer_lastname');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->string('customer_city')->nullable();
            $table->text('customer_address')->nullable();
            $table->foreignId('service_center_id')->nullable()->constrained('wms_service_centers')->onDelete('set null');
            $table->date('claim_date');
            $table->enum('status', ['Open', 'Closed', 'Converted'])->default('Open');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_claims');
    }
};
