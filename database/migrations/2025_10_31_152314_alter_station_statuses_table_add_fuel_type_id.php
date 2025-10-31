<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_statuses', function (Blueprint $table) {
            // Ajouter fuel_type_id (si la colonne n'existe pas encore)
            if (!Schema::hasColumn('station_statuses', 'fuel_type_id')) {
                $table->foreignId('fuel_type_id')
                      ->nullable() // temporairement nullable pour éviter les conflits
                      ->constrained()
                      ->onDelete('cascade');
            }

            // Modifier les valeurs possibles de status
            $table->enum('status', ['disponible', 'peu', 'rupture'])
                  ->default('disponible')
                  ->change();

            // Ajouter la contrainte unique (après avoir ajouté fuel_type_id)
            $table->unique(['station_id', 'fuel_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('station_statuses', function (Blueprint $table) {
            $table->dropUnique(['station_id', 'fuel_type_id']);
            $table->dropForeign(['fuel_type_id']);
            $table->dropColumn('fuel_type_id');

            $table->enum('status', ['disponible', 'rupture', 'attente'])
                  ->default('disponible')
                  ->change();
        });
    }
};
