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
        // Drop columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        // Recreate columns with modified enum
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('relationship_to_kid', ['Family', 'Friend', 'Teacher']);
            $table->boolean('terms')->default(0);
            $table->boolean('is_admin')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('relationship_to_kid', ['Family', 'Friend', 'Teacher']);
            $table->boolean('terms')->default(0);
            $table->boolean('is_admin')->default(false);
        });
    }
};
