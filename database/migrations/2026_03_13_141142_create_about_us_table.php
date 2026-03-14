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
        Schema::create('about_us', function (Blueprint $table) {
            $table->id();
            $table->string('banner_title', 255)->nullable();
            $table->string('banner_image')->nullable();
            $table->text('banner_description')->nullable();
            $table->text('story')->nullable();
            $table->string('story_image')->nullable();
            $table->text('our_promise')->nullable();
            $table->text('core_value')->nullable();
            $table->text('our_mission')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_us');
    }
};
