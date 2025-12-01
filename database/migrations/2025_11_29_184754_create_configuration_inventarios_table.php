<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('configuracion_inventarios', function (Blueprint $table) {
            $table->id();
            // En un sistema real, aquí iría 'institucion_id'. 
            // Para tu demo, puedes dejarlo o usar un ID fijo (ej. 1).
            $table->unsignedBigInteger('institucion_id')->default(1)->unique(); 
            
            // AQUÍ ESTÁ LA CLAVE: Tipo de dato JSON
            $table->json('configuracion_json'); 
            
            $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration_inventarios');
    }
};
