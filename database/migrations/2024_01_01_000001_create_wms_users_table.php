<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('user_type', ['admin', 'staff', 'client'])->default('staff');
            $table->boolean('is_admin')->default(false);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('image')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->boolean('disable_login')->default(false);
            $table->text('note')->nullable();
            $table->text('address')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('language')->default('en');
            $table->timestamp('last_online')->nullable();
            $table->boolean('enable_web_notification')->default(true);
            $table->boolean('enable_email_notification')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
