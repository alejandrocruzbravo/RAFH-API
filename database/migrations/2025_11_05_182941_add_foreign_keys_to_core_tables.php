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
        // 1. Conecta 'areas' con 'resguardantes'
        Schema::table('areas', function (Blueprint $table) {
            $table->foreign('id_resguardante_responsable')
                  ->references('id')->on('resguardantes')
                  ->onDelete('set null');
        });

        // 2. Conecta 'departamentos' con 'areas'
        Schema::table('departamentos', function (Blueprint $table) {
            $table->foreign('id_area')
                  ->references('id')->on('areas');
        });

        // 3. Conecta 'resguardantes' con 'departamentos' y 'oficinas'
        Schema::table('resguardantes', function (Blueprint $table) {
            $table->foreign('res_departamento')
                  ->references('id')->on('departamentos');
            
            $table->foreign('id_oficina')
                  ->references('id')->on('oficinas')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['id_resguardante_responsable']);
        });

        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropForeign(['id_area']);
        });

        Schema::table('resguardantes', function (Blueprint $table) {
            $table->dropForeign(['res_departamento']);
            $table->dropForeign(['id_oficina']);
        });
    }
};