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
        Schema::create('confirmaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('confirm_id_traspaso')->constrained('traspasos');
            $table->foreignId('confirm_id_usuario_autorizador')->constrained('usuarios');
            $table->timestamp('confim_fecha');
            $table->string('confirm_aprobado');
            $table->text('confirm_comentarios')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmaciones');
    }
};
