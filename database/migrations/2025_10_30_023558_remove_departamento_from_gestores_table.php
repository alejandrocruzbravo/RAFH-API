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
        Schema::table('gestores', function (Blueprint $table) {
            // Esto elimina la restricción de clave foránea
            // Y la columna 'gestor_departamento' al mismo tiempo.
            $table->dropConstrainedForeignId('gestor_departamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esto la vuelve a crear si haces un rollback
        Schema::table('gestores', function (Blueprint $table) {
            $table->foreignId('gestor_departamento')
                  ->constrained('departamentos');
        });
    }
};