<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->foreignId('engineer_id')->nullable()->after('courier_id')->constrained('users')->onDelete('set null');
            $table->foreignId('courier_in_id')->nullable()->after('engineer_id')->constrained('wms_couriers')->onDelete('set null');
            $table->foreignId('courier_out_id')->nullable()->after('courier_in_id')->constrained('wms_couriers')->onDelete('set null');
            $table->string('attachments')->nullable()->after('courier_out_id');
            $table->boolean('feedback_preference')->default(false)->after('attachments');
            $table->timestamp('received_date_time')->nullable()->after('feedback_preference');
            $table->timestamp('delivered_date_time')->nullable()->after('received_date_time');
            $table->integer('counter')->default(1)->after('delivered_date_time');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropForeign(['engineer_id']);
            $table->dropForeign(['courier_in_id']);
            $table->dropForeign(['courier_out_id']);
            $table->dropColumn([
                'engineer_id',
                'courier_in_id',
                'courier_out_id',
                'attachments',
                'feedback_preference',
                'received_date_time',
                'delivered_date_time',
                'counter',
            ]);
        });
    }
};
