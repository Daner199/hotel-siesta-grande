<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoSalonEvento extends Foto
{
    protected $table = 'foto_salon_evento';

    public function salon(): BelongsTo
    {
        return $this->belongsTo(SalonEvento::class, 'salon_evento_id');
    }
}
