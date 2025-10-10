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
        Schema::create('archivos_bien', function (Blueprint $table) {
            $table->id();
            $table->string('archivo_bien_codigo');
            $table->foreign('archivo_bien_codigo')->references('bien_codigo')->on('bienes');
            $table->string('archivo_tipo');
            $table->string('archivo_nombre');
            $table->string('archivo_url')->unique();
            $table->string('archivo_bucket');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos_bien');
    }
};
