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
        Schema::create('traspasos', function (Blueprint $table) {
            $table->id();
            $table->string('traspaso_codigo_bien');
            $table->foreign('traspaso_codigo_bien')->references('bien_codigo')->on('bienes');
            // $table->foreign('traspaso_id_usuario_origen')->references('id')->on('usuarios');
            // $table->foreign('traspaso_id_usuario_destino')->references('id')->on('usuarios');
            $table->foreignId('traspaso_id_usuario_origen')->constrained('usuarios');
            $table->foreignId('traspaso_id_usuario_destino')->constrained('usuarios');
            $table->timestamp('traspaso_fecha_solicitud');
            $table->string('traspaso_fecha_estado');
            $table->text('traspaso_fecha_observaciones')->nullable();
            $table->timestamps();
            
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traspasos');
    }
};
