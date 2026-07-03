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
        Schema::create('curate_travel_experiences', function (Blueprint $table) {
            $table->id();
            $table->string('country');
            $table->unsignedTinyInteger('number_of_adults')->default(1);
            $table->unsignedTinyInteger('number_of_kids')->default(0);
            $table->json('kids_ages')->nullable();
            $table->string('hotel_category')->nullable(); 
            $table->boolean('flight_booking')->default(false);
            $table->boolean('airport_transfer')->default(false);
            $table->boolean('tour_and_activities')->default(false);
            $table->text('special_request')->nullable();
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('email_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('curate_travel_experiences');
    }
};
