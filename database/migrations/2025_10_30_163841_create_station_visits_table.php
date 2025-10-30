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
        Schema::create('station_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();   // utile pour compter visiteurs uniques
            $table->string('device')->nullable();       // utile pour stats mobile/desktop
            $table->string('commune')->nullable();      // pour regroupement par zone
            $table->string('quartier')->nullable();     // plus précis que la commune
            $table->timestamp('visited_at')->useCurrent();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_visits');
    }
};
