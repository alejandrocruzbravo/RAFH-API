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
        // Esta función ELIMINA la columna
        Schema::table('gestores', function (Blueprint $table) {
            $table->dropColumn('gestor_puesto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esta función la AÑADE DE NUEVO si haces un rollback
        Schema::table('gestores', function (Blueprint $table) {
            // La colocamos de vuelta donde estaba (según tu diagrama)
            $table->string('gestor_puesto')->nullable()->after('gestor_apellido2');
        });
    }
};