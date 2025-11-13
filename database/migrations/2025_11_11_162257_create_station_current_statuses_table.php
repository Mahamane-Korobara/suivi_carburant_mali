<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_current_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->onDelete('cascade');
            $table->foreignId('fuel_type_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['disponible', 'peu', 'rupture']);
            $table->timestamps();

            $table->unique(['station_id', 'fuel_type_id']); // statuts actuels uniques
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_current_statuses');
    }
};
