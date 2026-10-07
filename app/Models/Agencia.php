<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agencia extends Model
{
    protected $table = 'agencia';

    // La tabla agencia no tiene created_at ni updated_at
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'nombre',
        'nit',
        'telefono',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    // Cada agencia pertenece a un usuario
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}