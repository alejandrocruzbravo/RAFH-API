<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\cucopClave;
use App\Models\ArchivoBien;
use Carbon\Carbon;

class Bien extends Model
{
    use HasFactory;

    // Especifica la clave primaria si no es 'id'
    protected $primaryKey = 'id'; // Actualizado para usar 'id' como llave primaria
    protected $table = 'bienes';
    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'bien_codigo', // <-- Ahora es un campo normal
        'bien_nombre',
        'bien_categoria',
        'bien_ubicacion_actual',
        'bien_estado',
        'bien_modelo',
        'bien_marca',
        'bien_fecha_adquision',
        'bien_valor_monetario',
        'bien_id_dep',
    ];

    static public function generarCodigo($serie,$clave = 0,$y=null) {
        $string = ($y !== null) ? 'I'.$clave.'-'.$y->format('y').'-23-'.$serie : 'I'.$clave.'-23-'.$serie;
        return strtoupper($string);
    }


    public function archivos(){
        return $this->hasMany(ArchivoBien::class);
    }



}         