<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Edificio;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Oficina;

class OrganigramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // --- 0. OBTENER EDIFICIOS (Asumiendo que el EdificioSeeder ya corrió) ---
            // Buscamos por el nombre exacto que creamos en el paso anterior
            $edificioL = Edificio::where('nombre', 'EDIFICIO L')->firstOrFail();
            $edificioR = Edificio::where('nombre', 'EDIFICIO R')->firstOrFail();
            $edificioV = Edificio::where('nombre', 'EDIFICIO V')->firstOrFail();
            $edificioN = Edificio::where('nombre', 'EDIFICIO N')->firstOrFail();
            $edificioS = Edificio::where('nombre', 'EDIFICIO S')->firstOrFail();

            // --- 1. CREAR ÁREA (NARANJA) ---
            $area = Area::firstOrCreate(
                ['area_codigo' => '002'],
                ['area_nombre' => 'SUBDIRECCIÓN DE SERVICIOS ADMINISTRATIVOS']
            );

            // =====================================================================
            // GRUPO 1: SUBDIRECCIÓN ADMVA (Actuando como Depto dentro del Área)
            // =====================================================================
            // Azul
            $depSub = Departamento::firstOrCreate(
                ['dep_codigo' => '002-DEP'], // Clave ajustada para no chocar con el área
                ['dep_nombre' => 'Subdirección Administrativa', 'id_area' => $area->id]
            );
            
            // Blanco (Edificio L)
            Oficina::firstOrCreate(
                ['ofi_codigo' => '02A'],
                ['nombre' => 'Secretaria de Subdirección Administrativa', 'id_edificio' => $edificioL->id, 'id_departamento' => $depSub->id]
            );

            // =====================================================================
            // GRUPO 2: RECURSOS HUMANOS
            // =====================================================================
            // Azul
            $depRH = Departamento::firstOrCreate(
                ['dep_codigo' => '003'],
                ['dep_nombre' => 'Departamento de Recursos Humanos', 'id_area' => $area->id]
            );

            // Blanco (Todos en Edificio L)
            Oficina::firstOrCreate(['ofi_codigo' => '03A'], ['nombre' => 'Secretaria del Departamento de Recursos Humanos', 'id_edificio' => $edificioL->id, 'id_departamento' => $depRH->id]);
            Oficina::firstOrCreate(['ofi_codigo' => '03B'], ['nombre' => 'Oficina de Registros y Controles', 'id_edificio' => $edificioL->id, 'id_departamento' => $depRH->id]);
            Oficina::firstOrCreate(['ofi_codigo' => '03C'], ['nombre' => 'Oficina de Servicios Al Personal', 'id_edificio' => $edificioL->id, 'id_departamento' => $depRH->id]);

            // =====================================================================
            // GRUPO 3: RECURSOS FINANCIEROS
            // =====================================================================
            // Azul
            $depRF = Departamento::firstOrCreate(
                ['dep_codigo' => '004'],
                ['dep_nombre' => 'Departamento de Recursos Financieros', 'id_area' => $area->id]
            );

            // Blanco (Todos en Edificio L)
            Oficina::firstOrCreate(['ofi_codigo' => '04A'], ['nombre' => 'Secretaría del Departamento de Recursos Financieros', 'id_edificio' => $edificioL->id, 'id_departamento' => $depRF->id]);
            Oficina::firstOrCreate(['ofi_codigo' => '04B'], ['nombre' => 'Oficina de Tesorería', 'id_edificio' => $edificioL->id, 'id_departamento' => $depRF->id]);
            Oficina::firstOrCreate(['ofi_codigo' => '04C'], ['nombre' => 'Oficina de Ingresos Propios', 'id_edificio' => $edificioL->id, 'id_departamento' => $depRF->id]);

            // =====================================================================
            // GRUPO 4: RECURSOS MATERIALES Y SERVICIOS
            // =====================================================================
            // Azul
            $depRMS = Departamento::firstOrCreate(
                ['dep_codigo' => '005'],
                ['dep_nombre' => 'Departamento de Recursos Materiales y Servicios', 'id_area' => $area->id]
            );

            // Blanco (Todos en Edificio R)
            Oficina::firstOrCreate(['ofi_codigo' => '05A'], ['nombre' => 'Secretaría del Departamento de Recursos Materiales y Servicios', 'id_edificio' => $edificioR->id, 'id_departamento' => $depRMS->id]);
            Oficina::firstOrCreate(['ofi_codigo' => '006'], ['nombre' => 'Oficina de Almacen y Activo Fijo', 'id_edificio' => $edificioR->id, 'id_departamento' => $depRMS->id]);

            // =====================================================================
            // GRUPO 5: CENTRO DE COMPUTO
            // =====================================================================
            // Azul
            $depCC = Departamento::firstOrCreate(
                ['dep_codigo' => '007'],
                ['dep_nombre' => 'Centro de Computo', 'id_area' => $area->id]
            );

            // Blanco (Edificio V)
            Oficina::firstOrCreate(['ofi_codigo' => '078'], ['nombre' => 'Sala Virtual', 'id_edificio' => $edificioV->id, 'id_departamento' => $depCC->id]);

            // =====================================================================
            // GRUPO 6: COORDINACIÓN DE SERVICIO DE COMPUTO
            // =====================================================================
            // Azul (Nota: La imagen muestra edificio N a la izquierda, pero al ser Depto no lleva ubicación directa en la DB,
            // a menos que creemos una oficina "base" para esta coordinación. Por ahora solo creo el Depto).
            $depCoord = Departamento::firstOrCreate(
                ['dep_codigo' => '048'],
                ['dep_nombre' => 'Coordinación de Servicio de Computo (Conectividad)', 'id_area' => $area->id]
            );
            // Si requieres una oficina para este depto en el Edificio N, descomenta esto:
            /*
            Oficina::firstOrCreate(['ofi_codigo' => '048-OFF'], ['nombre' => 'Oficina de Coordinación', 'id_edificio' => $edificioN->id, 'id_departamento' => $depCoord->id]);
            */

            // =====================================================================
            // GRUPO 7: MANTENIMIENTO Y EQUIPO
            // =====================================================================
            // Azul
            $depMant = Departamento::firstOrCreate(
                ['dep_codigo' => '008'],
                ['dep_nombre' => 'Departamento de Mantenimiento y Equipo', 'id_area' => $area->id]
            );

            // Blanco (Edificio S)
            Oficina::firstOrCreate(['ofi_codigo' => '08A'], ['nombre' => 'Secretaría del Departamento de Mantenimiento y Equipo', 'id_edificio' => $edificioS->id, 'id_departamento' => $depMant->id]);

        });
    }
}