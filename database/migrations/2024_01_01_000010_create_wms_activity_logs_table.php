<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wms_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->string('log_type')->nullable();
            $table->string('log_type_title')->nullable();
            $table->integer('log_type_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('log_for')->nullable();
            $table->integer('log_for_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wms_activity_logs');
    }
};
