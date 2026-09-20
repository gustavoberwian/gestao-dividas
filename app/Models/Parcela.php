<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parcela extends Model
{
    use HasFactory;

    protected $table = 'parcelas';

    protected $fillable = [
        'fatura_id',
        'divida_id',
        'numero_parcela',
        'valor',
        'data_vencimento',
        'status_pagamento',
    ];

    protected function casts(): array
    {
        return [
            'numero_parcela' => 'integer',
            'valor' => 'decimal:2',
            'data_vencimento' => 'date',
        ];
    }

    public function fatura(): BelongsTo
    {
        return $this->belongsTo(Fatura::class);
    }

    public function divida(): BelongsTo
    {
        return $this->belongsTo(Divida::class);
    }
}
