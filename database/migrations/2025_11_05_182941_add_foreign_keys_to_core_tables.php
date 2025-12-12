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

        // Conecta 'departamentos' con 'areas'
        Schema::table('departamentos', function (Blueprint $table) {
            $table->foreign('id_area')
                  ->references('id')->on('areas');
        });

        // Conecta 'resguardantes' con 'departamentos' y 'oficinas'
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

        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropForeign(['id_area']);
        });

        Schema::table('resguardantes', function (Blueprint $table) {
            $table->dropForeign(['res_departamento']);
            $table->dropForeign(['id_oficina']);
        });
    }
};