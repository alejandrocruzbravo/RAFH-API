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
        Schema::create('catalogo_camb_cucop', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->integer('clave_cucop');
            $table->string('partida_especifica');
            $table->string('clave_cucop_plus');
            $table->text('descripcion');
            $table->string('nivel');
            $table->string('camb');
            $table->string('unidad_medida');
            $table->string('tipo_contratacion');            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_camb_cucop');
    }
};
