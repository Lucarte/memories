<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id()->comment('PK from files');
            $table->string('file_type')->nullable(true)->comment('Type of file being uploaded');
            $table->string('file_path')->nullable()->comment('Path to the uploaded file');
            $table->timestamps();

            // Foreign key constraints
            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('memory_id')
                ->index()
                ->constrained('memories')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
