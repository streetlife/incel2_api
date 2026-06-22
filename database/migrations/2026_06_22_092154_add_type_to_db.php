<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sessions_hotels', function (Blueprint $table) {
            //
            $table->text('rooms_adults')->nullable()->after('rooms');
            $table->text('rooms_children')->nullable()->after('rooms_adults');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions_hotels', function (Blueprint $table) {
            //
        });
    }
};
