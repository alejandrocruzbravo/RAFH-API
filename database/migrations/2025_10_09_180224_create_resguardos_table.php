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
        Schema::create('resguardos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resguardo_id_bien')->constrained('bienes');
            $table->foreignId('resguardo_id_resguardante')->constrained('resguardantes');
            $table->timestamp('resguardo_fecha_asignacion');
            $table->string('resguardo_observacion')->nullable();
            $table->foreignId('resguardo_id_dep')->constrained('departamentos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resguardos');
    }
};
