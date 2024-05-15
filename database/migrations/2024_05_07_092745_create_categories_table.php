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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['Music', 'Sports', 'Dance', 'Viola', 'Musical Theater', 'Programming', 'Art', 'Various', 'Climbing', 'Running', 'Swimming', 'Harmonica', 'IJK', 'FJO', 'CMS', 'Theater', 'Horse Riding', 'Meditation', 'Cold Plunges'])->comment('Category a memory belongs to, from either kid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
