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
            // 1. Renombra 'gestor_apellido1' a 'gestor_apellidos'
            $table->renameColumn('gestor_apellido1', 'gestor_apellidos');
            // 2. Elimina 'gestor_apellido2' y 'gestor_telefono'
            $table->dropColumn(['gestor_apellido2', 'gestor_telefono']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revierte los cambios en orden opuesto
        Schema::table('gestores', function (Blueprint $table) {
            
            // 2. Vuelve a añadir las columnas eliminadas
            $table->string('gestor_apellido2')->nullable()->after('gestor_apellido1');
            $table->string('gestor_telefono')->nullable()->after('gestor_departamento');

            // 1. Revierte el renombrado
            $table->renameColumn('gestor_apellidos', 'gestor_apellido1');
        });
    }
};