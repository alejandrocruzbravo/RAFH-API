<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\cucopClave;
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

    static public function generarCodigo($serie,array $fitros) {
        $cucop=null;
        foreach($fitros as $campo=> $valor){
            $cucop = cucopClave::where($campo,$valor)->first();
        };
        $string = 'I'.$cucop->cucop.'-'.Carbon::now()->format('y').'-23-'.$serie;
        return $string;
    }



}         