<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Bien;
use Carbon\Carbon; 

class BienTest extends TestCase
{
    /**
     * Prueba que el código se genera correctamente cuando SOLO se pasa la serie.
     */
    public function test_genera_codigo_basico_solo_con_serie()
    {
        // Escenario: Serie "ABC", sin clave (default 0), sin fecha (null)
        $serie = 'ABC';
        $codigo = Bien::generarCodigo($serie);

        //  'I'.0.'-23-'.'ABC' -> I0-23-ABC
        $this->assertEquals('I0-23-ABC', $codigo);
    }

    /**
     * Prueba que el código se genera correctamente con Clave y Año personalizado.
     */
    public function test_genera_codigo_completo_con_clave_y_fecha()
    {
        // Escenario: Serie "XYZ", Clave 555, Año 2025
        $serie = 'XYZ';
        $clave = 555;
        $fecha = Carbon::create(2025, 1, 1);
        
        $codigo = Bien::generarCodigo($serie, $clave, $fecha);

        // L'I'.$clave.'-'.$y->format('y').'-23-'.$serie
        // Resultado esperado: I555-25-23-XYZ
        $this->assertEquals('I555-25-23-XYZ', $codigo);
    }
    
    /**
     * Prueba que el código se convierte a mayúsculas automáticamente.
     */
    public function test_convierte_codigo_a_mayusculas()
    {
        $serie = 'abc';
        $codigo = Bien::generarCodigo($serie);
        $this->assertEquals('I0-23-ABC', $codigo);
    }
}