<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("catalogo_cucop", function (Blueprint $table) {
            $table->id();
            $table->string("cucop_plus");
            $table->integer("cucop");
            $table->text("descripcion");
            $table->integer("partida_especifica");
            $table->string("descripcion_partida_especifia");
            $table->integer("partida_generica");
            $table->string("descripcion_partida_generica");
            $table->integer("concepto");
            $table->string('descripcion_concepto');
            $table->integer("capitulo");
            $table->timestamp("fecha_alta_cucop")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("catalogo_cucop");
    }
};
