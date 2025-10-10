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
        Schema::create('usuario_permisos', function (Blueprint $table) {
            $table->id();
            $table->timestamp('permiso_fecha_otorgado');
            $table->foreignId('permiso_otorgado_por')->constrained('usuarios');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('permisos_id')->constrained('permisos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_permisos');
    }
};
