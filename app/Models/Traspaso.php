<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Traspaso extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'traspasos';

    /**
     * La llave primaria asociada con la tabla.
     */
    protected $primaryKey = 'id';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'traspaso_id_bien',
        'traspaso_id_usuario_origen',
        'traspaso_id_usuario_destino',
        'traspaso_fecha_solicitud',
        'traspaso_estado',
        'traspaso_observaciones',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'traspaso_fecha_solicitud' => 'date',
    ];
    /**
     * Obtiene el bien que está siendo traspasado.
     */
    public function bien()
    {
        // Un traspaso pertenece a un Bien
        return $this->belongsTo(Bien::class, 'traspaso_id_bien', 'id');
    }

    /**
     * Obtiene el usuario (Resguardante/Gestor) que origina el traspaso.
     */
    public function usuarioOrigen()
    {
        // Un traspaso pertenece a un Usuario (como origen)
        return $this->belongsTo(Usuario::class, 'traspaso_id_usuario_origen', 'id');
    }

    /**
     * Obtiene el usuario (Resguardante/Gestor) que recibe el traspaso.
     */
    public function usuarioDestino()
    {
        // Un traspaso pertenece a un Usuario (como destino)
        return $this->belongsTo(Usuario::class, 'traspaso_id_usuario_destino', 'id');
    }
    
    public function confirmacion()
    {
        return $this->hasOne(Confirmacion::class, 'confirm_id_traspaso');
    }
}