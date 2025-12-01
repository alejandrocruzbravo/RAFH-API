<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resguardo;
use App\Models\MovimientoBien;
use App\Models\Traspaso;
use App\Models\Oficina;
use App\Models\ArchivoBien;


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
    static public function generarCodigo($cambBase) 
    {
        // 1. Obtener el Separador de la Configuración (Para mantener coherencia)
        // Si no quieres consultar la BD de config cada vez, puedes hardcodear '-' 
        // pero esto es más robusto:
        $configModel = ConfiguracionInventario::first();
        $separator = $configModel->configuracion_json['structure']['separator'] ?? '-';
        if ($separator === 'Ninguno') $separator = '';

        // 2. Buscar el último bien que empiece con esta base
        // Buscamos: "I51101-0001-25-23" + Separador + "%"
        $prefixBusqueda = $cambBase . $separator;

        $ultimoBien = self::where('bien_codigo', 'like', $prefixBusqueda . '%')
            // Ordenamos por longitud primero para que "10" sea mayor que "2"
            ->orderByRaw('LENGTH(bien_codigo) DESC') 
            ->orderBy('bien_codigo', 'desc')
            ->first();

        $consecutivo = 1;

        if ($ultimoBien) {
            // 3. Extraer la secuencia numérica final
            // Ejemplo: Tenemos "I51101...-0005". Quitamos el prefijo y queda "0005".
            $codigoCompleto = $ultimoBien->bien_codigo;
            
            // Reemplazamos la base por vacío para obtener solo el final
            $soloSecuencia = str_replace($prefixBusqueda, '', $codigoCompleto);
            
            if (is_numeric($soloSecuencia)) {
                $consecutivo = intval($soloSecuencia) + 1;
            }
        }

        // 4. Formatear con ceros (Padding)
        // Usamos 5 dígitos para el inventario físico para aguantar hasta 99,999 items iguales
        // O puedes leer este valor también de la config si prefieres.
        $secuenciaStr = str_pad($consecutivo, 5, '0', STR_PAD_LEFT);

        // 5. Retornar Código Final
        // Ej: "I51101-0001-25-23" + "-" + "00001"
        return strtoupper($cambBase . $separator . $secuenciaStr);
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
}