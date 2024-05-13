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
        Schema::create('memories', function (Blueprint $table) {
            $table->id()->comment('PK from memories');
            $table->enum('category', ['Music', 'Sport', 'Dance', 'Viola', 'Musical Theater', 'Programming', 'Art', 'Various'])->comment('Category a memory belongs to');
            $table->string('title', 100)->unique()->comment('Title of the memory');
            $table->text('description')->comment('Description of the memory');
            $table->string('kid', 9)->comment('Is this Pablo\'s or Gabi\'s memory?');
            $table->timestamps();

            // Foreign key constraints with ON UPDATE / ON DELETE CASCADE

            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};
