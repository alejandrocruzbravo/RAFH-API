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
        Schema::create('bienes', function (Blueprint $table) {
            $table->string('bien_codigo')->primary();
            $table->string('bien_nombre');
            $table->string('bien_categoria');
            $table->string('bien_ubicacion_actual');
            $table->string('bien_estado');
            $table->string('bien_modelo');
            $table->string('bien_marca');
            $table->timestamp('bien_fecha_adquision');
            $table->unsignedInteger('bien_valor_monetario');
            $table->foreignId('bien_id_dep')->constrained('departamentos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bienes');
    }
};
