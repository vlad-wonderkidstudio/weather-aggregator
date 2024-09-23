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
    Schema::create('weather_data', function (Blueprint $table) {
      $table->id();
      $table->string('source_code', 64)->index();
      $table->float('temperature');
      $table->timestamp('date')->index();
      $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('weather_data');
  }
};
