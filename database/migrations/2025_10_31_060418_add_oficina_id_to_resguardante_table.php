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
            $table->foreignId('id_oficina')
                  ->nullable() // Hacemos que sea opcional
                  ->after('res_id_usuario') // La ponemos al final
                  ->constrained('oficinas') // Apunta a la tabla 'oficinas'
                  ->onDelete('set null'); // Si se borra la oficina, el resguardante no
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resguardantes', function (Blueprint $table) {
            // Elimina la llave foránea y la columna
            $table->dropConstrainedForeignId('id_oficina');
        });
    }
};