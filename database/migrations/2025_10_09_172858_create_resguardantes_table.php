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
        Schema::create('resguardantes', function (Blueprint $table) {
            $table->id();
            $table->string('res_nombre');
            $table->string('res_apellido1');
            $table->string('res_apellido2')->nullable();
            $table->string('res_puesto')->nullable();
            $table->string('res_correo')->nullable();
            $table->foreignId('res_departamento')->constrained('departamentos');
            $table->string('res_telefono');
            $table->foreignId('res_id_usuario')->constrained('usuarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resguardantes');
    }
};
