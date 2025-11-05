<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Esta migración corre DESPUÉS de que 'resguardantes' existe
        Schema::table('areas', function (Blueprint $table) {
            $table->foreign('id_resguardante_responsable')
                  ->references('id')
                  ->on('resguardantes')
                  ->onDelete('set null'); // Opcional: si se borra el resguardante, pone el campo en null
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['id_resguardante_responsable']);
        });
    }
};