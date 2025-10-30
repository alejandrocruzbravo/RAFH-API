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
        // Esta función ELIMINA la columna
        Schema::table('departamentos', function (Blueprint $table) {
            $table->dropColumn('dep_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departamentos', function (Blueprint $table) {
            // Asumo que era un 'text' y la coloco después de 'dep_nombre'
            $table->text('dep_description')->nullable()->after('dep_nombre');
        });
    }
};
