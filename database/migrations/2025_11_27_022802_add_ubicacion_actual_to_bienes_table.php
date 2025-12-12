<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bienes', function (Blueprint $table) {
            $table->unsignedBigInteger('bien_ubicacion_actual')->nullable()->after('id_oficina');
            
            $table->foreign('bien_ubicacion_actual')->references('id')->on('oficinas');
        });

        DB::statement('UPDATE bienes SET bien_ubicacion_actual = id_oficina');
    }

    public function down()
    {
        Schema::table('bienes', function (Blueprint $table) {
            $table->dropForeign(['bien_ubicacion_actual']);
            $table->dropColumn('bien_ubicacion_actual');
        });
    }
};

