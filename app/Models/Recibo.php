<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recibo de un pago aprobado (no es una factura). Numerado sin saltos: REC-000001.
 * saldo_pendiente es el saldo en el momento de emitirlo (no cambia después).
 * Se emite con App\Support\Recibos.
 */
class Recibo extends Model
{
    protected $table = 'recibo';

    public $timestamps = false;

    protected $fillable = ['numero', 'pago_id', 'fecha_emision', 'saldo_pendiente', 'token_verificacion'];

    protected $casts = [
        'numero'          => 'integer',
        'fecha_emision'   => 'datetime',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    // 12 → "REC-000012"
    public function codigo(): string
    {
        return 'REC-' . str_pad((string) $this->numero, 6, '0', STR_PAD_LEFT);
    }

    // URL pública del QR de verificación (se abre con el celular)
    public function urlVerificacion(): string
    {
        return \App\Support\Recibos::urlPublica(route('recibos.verificar', $this->token_verificacion, false));
    }
}
