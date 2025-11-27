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
        Schema::table('bienes', function (Blueprint $table) {
            // 1. Creamos la columna. Nullable es vital porque un bien puede estar en bodega sin resguardante.
            // 'after' es opcional, solo para ordenar visualmente en la DB.
            $table->unsignedBigInteger('id_resguardante')->nullable()->after('id_oficina');

            // 2. Creamos la llave foránea
            // onDelete('set null'): Si borras al empleado, el bien no se borra, solo se queda "sin asignar".
            $table->foreign('id_resguardante')
                  ->references('id')
                  ->on('resguardantes')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bienes', function (Blueprint $table) {
            // Primero borramos la relación (constraint) y luego la columna
            $table->dropForeign(['id_resguardante']);
            $table->dropColumn('id_resguardante');
        });
    }
};