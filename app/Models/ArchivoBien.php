<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Bien;
class ArchivoBien extends Model
{
    //
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'archivos_bien';
    protected $fillable = [
        'archivos_id_bien',
        'archivo_nombre',
        'archivo_url',
        'archivo_bucket'
    ];


    public function bien() {
        return $this->belongsTo(Bien::class);
    }
}
