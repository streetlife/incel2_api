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
        Schema::table('bookings_hotels', function (Blueprint $table) {
            //
            $table->string("rezlivebookingId")->nullable();
            $table->string("rezliveBookingCode")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings_hotels', function (Blueprint $table) {
            //
        });
    }
};
