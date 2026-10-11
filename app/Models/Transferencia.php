<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Datos de un pago por transferencia: número de operación y foto del comprobante
 * (en storage/app/private, no es público). El pago queda PENDIENTE ("en revisión")
 * hasta que recepción lo aprueba o lo rechaza.
 */
class Transferencia extends Model
{
    protected $table = 'transferencia';

    protected $primaryKey = 'pago_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['pago_id', 'numero_operacion', 'comprobante', 'revisado_por_usuario_id', 'fecha_revision', 'motivo_rechazo'];

    protected $casts = ['fecha_revision' => 'datetime'];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por_usuario_id');
    }

    public function esPdf(): bool
    {
        return str_ends_with(strtolower($this->comprobante), '.pdf');
    }
}
