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
        // Modifica la columna 'usuario_pass' para que ya NO sea 'unique'
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('usuario_pass')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vuelve a añadir la restricción 'unique' si haces rollback
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('usuario_pass')->unique()->change();
        });
    }
};