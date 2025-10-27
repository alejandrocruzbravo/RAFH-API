<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id_bien')->constrained('bienes');
            $table->string('mantenimiento_tipo'); // Ej. "Preventivo", "Correctivo"
            $table->text('mantenimiento_observaciones');
            $table->string('mantenimiento_estado'); // Ej. "Programado", "Completado"
            $table->date('fecha_programada'); // La fecha para el widget
            $table->date('fecha_completado')->nullable(); // Se rellena al terminar
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};