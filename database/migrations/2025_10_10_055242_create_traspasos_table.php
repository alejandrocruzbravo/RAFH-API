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
            $table->foreignId('traspaso_id_bien')->constrained('bienes');
            // $table->foreign('traspaso_id_usuario_origen')->references('id')->on('usuarios');
            // $table->foreign('traspaso_id_usuario_destino')->references('id')->on('usuarios');
            $table->foreignId('traspaso_id_usuario_origen')->constrained('usuarios');
            $table->foreignId('traspaso_id_usuario_destino')->constrained('usuarios');
            $table->timestamp('traspaso_fecha_solicitud');
           //$table->timestamp('traspaso_fecha_estado');
            $table->string('traspaso_estado');
            $table->text('traspaso_observaciones')->nullable();
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
