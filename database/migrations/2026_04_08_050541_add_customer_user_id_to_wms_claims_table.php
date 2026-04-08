<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_user_id')->nullable()->after('created_by');
            $table->foreign('customer_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_claims', function (Blueprint $table) {
            $table->dropForeign(['customer_user_id']);
            $table->dropColumn('customer_user_id');
        });
    }
};
