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
        // Usamos Schema::table() para modificar una tabla existente
        Schema::table('departamentos', function (Blueprint $table) {
            // Cambiamos la columna 'dep_resposable' para que acepte nulos (nullable)
            $table->string('dep_resposable')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esto revierte el cambio si ejecutas "migrate:rollback"
        Schema::table('departamentos', function (Blueprint $table) {
            $table->string('dep_resposable')->nullable(false)->change();
        });
    }
};