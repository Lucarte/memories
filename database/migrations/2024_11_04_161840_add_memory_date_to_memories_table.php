<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('memories', function (Blueprint $table) {
        $table->date('memory_date')->nullable()->after('day')->comment('Combined date for sorting');
    });
}

public function down(): void
{
    Schema::table('memories', function (Blueprint $table) {
        $table->dropColumn('memory_date');
    });
}

};

