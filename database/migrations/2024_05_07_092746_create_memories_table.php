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
            $table->enum('kid', ['Pablo', 'Gabriella', 'Both'])->comment('Choose Pablo, Gabriella, or Both');
            $table->string('title', 100)->unique()->comment('Title of the memory');
            $table->text('description')->comment('Description of the memory');
            $table->year('year', 4)->comment('Year a memory belongs to');
            $table->enum('month', ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'])->comment('Month a memory belongs to');
            $table->enum('day', ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'])->comment('Day a memory belongs to');
            $table->timestamps();

            // Foreign key constraints with ON UPDATE / ON DELETE CASCADE

            $table->foreignId('user_id')
                ->index()
                ->constrained()
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
