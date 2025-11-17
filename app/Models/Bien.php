<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    use HasFactory;

    protected $table = 'bienes';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'bien_codigo',
        'id_oficina',
        'bien_estado',
        'bien_marca',
        'bien_modelo',
        'bien_serie',
        'bien_descripcion',
        'bien_tipo_adquisicion',
        'bien_fecha_alta',
        'bien_valor_monetario',
        'bien_clave',
        'bien_y',
        'bien_secuencia',
        'bien_provedor',
        'bien_numero_factura',
    ];

    /**
     * Obtiene los registros de resguardo (custodia) de este bien.
     */
    public function resguardos()
    {
        return $this->hasMany(Resguardo::class, 'resguardo_id_bien');
    }

    /**
     * Obtiene los movimientos (historial) de este bien.
     */
    public function movimientosBien()
    {
        return $this->hasMany(MovimientoBien::class, 'movimiento_id_bien');
    }

    /**
     * Obtiene las solicitudes de traspaso de este bien.
     */
    public function traspasos()
    {
        return $this->hasMany(Traspaso::class, 'traspaso_id_bien');
    }
    /**
     * Obtiene la oficina donde este bien está físicamente ubicado.
     */
    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'id_oficina');
    }
        static public function generarCodigo($serie,$clave = 0,$y=null) {
        $string = ($y !== null) ? 'I'.$clave.'-'.$y->format('y').'-23-'.$serie : 'I'.$clave.'-23-'.$serie;
        return strtoupper($string);
    }


    public function archivos(){
        return $this->hasMany(ArchivoBien::class);
    }
}