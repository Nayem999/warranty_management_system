<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->after('replace_ref');
            $table->date('invoice_date')->nullable()->after('invoice_no');
            $table->decimal('purchase_price', 12, 2)->nullable()->after('invoice_date');
            $table->string('ref')->nullable()->after('purchase_price');
            $table->date('web_wty_date')->nullable()->after('ref');
        });
    }

    public function down(): void
    {
        Schema::table('wms_work_orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'invoice_date', 'purchase_price', 'ref', 'web_wty_date']);
        });
    }
};
