b<?php

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
        Schema::create('bienes', function (Blueprint $table) {
            $table->id();
            $table->string('bien_codigo')->unique();
            $table->string('bien_ubicacion_actual');
            $table->string('bien_marca');
            $table->string('bien_modelo');
            $table->string('bien_serie');
            $table->text('bien_descripcion');
            $table->string('bien_tipo_adquisicion');
            $table->timestamp('bien_fecha_alta')->nullable();
            $table->decimal('bien_valor_monetario',10,2);
            $table->string('bien_clave');
            $table->string('bien_y');
            $table->string('bien_secuencia');
            $table->string('bien_provedor');
            $table->string('bien_numero_factura');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bienes');
    }
};
