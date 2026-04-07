<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_warranties', function (Blueprint $table) {
            $table->dropColumn(['is_void', 'void_reason']);
        });
    }

    public function down(): void
    {
        Schema::table('wms_warranties', function (Blueprint $table) {
            $table->enum('is_void', ['YES', 'NO'])->default('NO')->after('end_date');
            $table->text('void_reason')->nullable()->after('is_void');
        });
    }
};
