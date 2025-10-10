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
        Schema::create('gestores', function (Blueprint $table) {
            $table->id();
            $table->string('gestor_nombre');
            $table->string('gestor_apellido1');
            $table->string('gestor_apellido2')->nullable();
            $table->string('gestor_puesto')->nullable();
            $table->string('gestor_correo')->nullable();
            $table->foreignId('gestor_departamento')->constrained('departamentos');
            $table->string('gestor_telefono');
            $table->foreignId('gestor_id_usuario')->constrained('usuarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestores');
    }
};
