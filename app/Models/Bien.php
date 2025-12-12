<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resguardo;
use App\Models\MovimientoBien;
use App\Models\Traspaso;
use App\Models\Oficina;
use App\Models\ArchivoBien;

use OpenApi\Annotations as OA; 
/**
 * @OA\Schema(
 * schema="Bien",
 * title="Bien (Activo Fijo)",
 * description="Modelo principal que representa un activo fijo del inventario.",
 * required={"bien_codigo", "bien_descripcion", "bien_valor_monetario", "bien_clave"},
 * @OA\Xml(name="Bien"),
 * * @OA\Property(property="id", type="integer", format="int64", description="ID único del bien", example=101),
 * @OA\Property(property="bien_codigo", type="string", description="Código único de inventario", example="TI-2024-005-01"),
 * @OA\Property(property="bien_clave", type="string", description="Clave de catálogo (prefijo)", example="51101-001"),
 * @OA\Property(property="bien_descripcion", type="string", description="Descripción detallada", example="Laptop Dell Latitude 5420"),
 * @OA\Property(property="bien_caracteristicas", type="string", nullable=true, description="Detalles técnicos adicionales", example="16GB RAM, 512GB SSD"),
 * @OA\Property(property="bien_marca", type="string", nullable=true, example="Dell"),
 * @OA\Property(property="bien_modelo", type="string", nullable=true, example="Latitude 5420"),
 * @OA\Property(property="bien_serie", type="string", nullable=true, description="Número de serie del fabricante", example="8H2J9K1"),
 * @OA\Property(property="bien_estado", type="string", description="Estado actual del bien", example="Activo", enum={"Activo", "Baja", "En tránsito", "Extraviado"}),
 * @OA\Property(property="bien_valor_monetario", type="number", format="float", description="Costo del bien", example=25000.50),
 * @OA\Property(property="bien_tipo_adquisicion", type="string", nullable=true, description="Forma de compra", example="Licitación"),
 * @OA\Property(property="bien_provedor", type="string", nullable=true, example="Proveedores del Norte S.A."),
 * @OA\Property(property="bien_numero_factura", type="string", nullable=true, example="FAC-998877"),
 * @OA\Property(property="bien_fecha_alta", type="string", format="date", nullable=true, description="Fecha de ingreso al inventario", example="2024-01-15"),
 * @OA\Property(property="bien_foto", type="string", nullable=true, description="Ruta relativa de la imagen almacenada", example="bienes/foto_101.jpg"),
 * @OA\Property(property="foto_url", type="string", nullable=true, description="URL pública completa de la imagen (Appended Attribute)", example="http://mi-api.com/storage/bienes/foto_101.jpg"),
 * * @OA\Property(property="id_oficina", type="integer", description="ID de la oficina administrativa de origen", example=5),
 * @OA\Property(property="id_resguardante", type="integer", nullable=true, description="ID del resguardante (dueño) actual", example=12),
 * @OA\Property(property="bien_ubicacion_actual", type="integer", nullable=true, description="ID de la oficina donde se encuentra físicamente", example=8),
 * * @OA\Property(property="bien_y", type="string", nullable=true, description="Año del ejercicio", example="2024"),
 * @OA\Property(property="bien_secuencia", type="string", nullable=true, description="Número consecutivo dentro del lote", example="00001"),
 * * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación en sistema"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Última actualización"),
 * @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, description="Fecha de eliminación (SoftDelete)")
 * )
 */
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
        'id_resguardante',
        'bien_estado',
        'bien_marca',
        'bien_modelo',
        'bien_serie',
        'bien_descripcion',
        'bien_foto',
        'bien_caracteristicas',
        'bien_tipo_adquisicion',
        'bien_fecha_alta',
        'bien_valor_monetario',
        'bien_clave',
        'bien_y',
        'bien_secuencia',
        'bien_provedor',
        'bien_numero_factura',
        'bien_ubicacion_actual'
    ];  

    protected $appends = ['foto_url'];
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
    
/**
     * Genera el código único del bien físico.
     * Recibe la clave del catálogo (Ej: "I51101-0001-25-23")
     * Retorna la clave del bien (Ej: "I51101-0001-25-23-00001")
     */
    static public function generarCodigo($serie,$clave = 0,$y=null)
    {
        $string = ($y !== null) ? 'I'.$clave.'-'.$y->format('y').'-23-'.$serie : 'I'.$clave.'-23-'.$serie;
        return strtoupper($string);
    }
    
    public function archivos(){
        return $this->hasMany(ArchivoBien::class);
    }
    /**
     * Relación: Obtiene el resguardante ACTUAL del bien.
     */
    public function resguardanteActual()
    {
        return $this->belongsTo(Resguardante::class, 'id_resguardante');
    }

    public function ubicacionActual()
    {
        return $this->belongsTo(Oficina::class, 'bien_ubicacion_actual','id');
    }

    public function getFotoUrlAttribute()
    {
        if ($this->bien_foto) {
            return asset('storage/' . $this->bien_foto);
        }
        // Puedes retornar null o una imagen por defecto
        return null; 
    }

    public function traspasoPendiente()
    {
        return $this->hasOne(Traspaso::class, 'traspaso_id_bien')
                    ->where('traspaso_estado', 'Pendiente')
                    ->latest(); // Por si hubiera error y hay dos, toma el último
    }
        protected static function booted()
    {
        static::deleting(function ($bien) {
            // Antes de eliminar el bien, elimina sus movimientos físicamente
            $bien->movimientosBien()->delete();
        });
    }
}