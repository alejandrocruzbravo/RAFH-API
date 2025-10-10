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
        Schema::create('movimientos_bien', function (Blueprint $table) {
            $table->id();
            $table->string('movimiento_codigo_bien');
            $table->foreign('movimiento_codigo_bien')->references('bien_codigo')->on('bienes');
            $table->foreignId('movimiento_id_dep')->constrained('departamentos');
            $table->timestamp('movimiento_fecha');
            $table->string('moviento_tipo');
            $table->foreignId('movimiento_id_usuario_origen')->constrained('usuarios');
            $table->foreignId('movimiento_id_usuario_destino')->constrained('usuarios');
            $table->foreignId('movimiento_id_usuario_autorizado')->constrained('usuarios');
            $table->text('movimiento_observaciones')->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_bien');
    }
};
