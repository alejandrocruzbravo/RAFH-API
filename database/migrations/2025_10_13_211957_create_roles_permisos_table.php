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
        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->bigInteger('id_rol');
            $table->bigInteger('id_permiso');
            $table->foreign('id_rol')->references('id')->on('roles');
            $table->foreign('id_permiso')->references('id')->on('permisos');
            $table->unique(['id_rol','id_permiso']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_permisos');
    }
};
