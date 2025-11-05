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
            $table->string('res_apellidos');   
            $table->string('res_puesto');
            $table->string('res_rfc', 13)->nullable()->unique();
            $table->string('res_curp', 18)->nullable()->unique();
            $table->string('res_correo')->unique();
            $table->string('res_telefono')->nullable(); 
            $table->foreignId('res_id_usuario')
                  ->nullable() 
                  ->constrained('usuarios');

            $table->unsignedBigInteger('res_departamento');
            $table->unsignedBigInteger('id_oficina')->nullable();
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