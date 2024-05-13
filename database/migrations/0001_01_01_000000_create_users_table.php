<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('PK from users');
            $table->string('first_name')->comment('First name of person registering');
            $table->string('last_name')->comment('Last name of person registering');
            $table->string('email')->unique()->comment('E-Mail of person registering');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->comment('Password used to login later');
            $table->enum('relationship_to_kid', ['Family', 'Friend', 'Teacher']);
            $table->boolean('terms')->default(0)->comment('Agreeance ot the terms and conditions');
            $table->boolean('is_admin')->default(false)->comment('Hat this person an admin role');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
