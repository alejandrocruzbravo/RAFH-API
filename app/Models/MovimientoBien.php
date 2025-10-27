<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoBien extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'movimientos_bien';

    /**
     * La llave primaria asociada con la tabla.
     */
    protected $primaryKey = 'id'; // Corregido

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'movimiento_id_bien', // Corregido
        'movimiento_id_dep',  // Añadido
        'movimiento_fecha',
        'movimiento_tipo',
        'movimiento_id_usuario_origen',
        'movimiento_id_usuario_destino',
        'movimiento_id_usuario_autorizado', // Corregido
        'movimiento_observaciones',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'movimiento_fecha' => 'datetime',
    ];

    // --- RELACIONES ---

    /**
     * Obtiene el bien asociado con el movimiento.
     */
    public function bien()
    {
        // Apunta a 'movimiento_id_bien' en esta tabla
        return $this->belongsTo(Bien::class, 'movimiento_id_bien', 'id');
    }

    /**
     * Obtiene el departamento asociado con el movimiento.
     */
    public function departamento()
    {
        // Apunta a 'movimiento_id_dep' en esta tabla
        return $this->belongsTo(Departamento::class, 'movimiento_id_dep', 'id');
    }

    /**
     * Obtiene el usuario que origina el movimiento.
     */
    public function usuarioOrigen()
    {
        return $this->belongsTo(Usuario::class, 'movimiento_id_usuario_origen', 'id');
    }

    /**
     * Obtiene el usuario que recibe el movimiento.
     */
    public function usuarioDestino()
    {
        return $this->belongsTo(Usuario::class, 'movimiento_id_usuario_destino', 'id');
    }

    /**
     * Obtiene el usuario que autorizó el movimiento.
     */
    public function usuarioAutorizado()
    {
        // Apunta a 'movimiento_id_usuario_autorizado'
        return $this->belongsTo(Usuario::class, 'movimiento_id_usuario_autorizado', 'id');
    }
}