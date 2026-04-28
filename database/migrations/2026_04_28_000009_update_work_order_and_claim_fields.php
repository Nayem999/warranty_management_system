<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE wms_work_orders DROP FOREIGN KEY wms_work_orders_replaced_warranty_id_foreign');
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'feedback_preference', 'replaced_warranty_id']);
        });

        Schema::table('wms_claims', function (Blueprint $table) {
            $table->text('attachments')->nullable()->after('claim_date');
            $table->boolean('is_feedback_taken')->default(false)->after('customer_rating');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->text('attachments')->nullable();
            $table->boolean('feedback_preference')->default(false);
            $table->foreignId('replaced_warranty_id')->nullable()->constrained('wms_warranties')->onDelete('set null');
        });

        Schema::table('wms_claims', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'is_feedback_taken']);
        });
    }
};