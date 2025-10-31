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
        Schema::table('resguardantes', function (Blueprint $table) {
            // 1. Renombrar res_apellido1 -> res_apellidos
            $table->renameColumn('res_apellido1', 'res_apellidos');

            // 2. Eliminar res_apellido2
            $table->dropColumn('res_apellido2');

            // 3. Añadir res_rfc (lo hacemos nulable y único)
            $table->string('res_rfc', 13)->nullable()->unique()->after('res_puesto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Invierte los cambios en orden opuesto
        Schema::table('resguardantes', function (Blueprint $table) {
            // 3. Eliminar res_rfc
            $table->dropColumn('res_rfc');

            // 2. Volver a añadir res_apellido2
            $table->string('res_apellido2')->nullable()->after('res_puesto');

            // 1. Renombrar res_apellidos -> res_apellido1
            $table->renameColumn('res_apellidos', 'res_apellido1');
        });
    }
};