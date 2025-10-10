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
        Schema::create('administradores', function (Blueprint $table) {
            $table->id();
            $table->string('admin_name');
            $table->string('admin_apellido1');
            $table->string('admin_apellido2')->nullable();
            $table->string('admin_puesto')->nullable();
            $table->string('admin_correo')->nullable();
            $table->string('admin_telefono');
            $table->foreignId('admin_id_usuario')->constrained('usuarios');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administradores');
    }
};
