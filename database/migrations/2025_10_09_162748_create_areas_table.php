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
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('area_codigo')->unique();
            $table->string('area_nombre');
            
            // Relación con Edificios (Esta SÍ se queda)
            $table->foreignId('id_edificio')
                  ->nullable()
                  ->constrained('edificios');
            
            // --- CADENA ROTA ---
            // Creamos la columna, pero sin la llave foránea por ahora
            $table->unsignedBigInteger('id_resguardante_responsable')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
