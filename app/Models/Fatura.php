<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fatura extends Model
{
    use HasFactory;

    protected $table = 'faturas';

    protected $fillable = [
        'cartao_credito_id',
        'mes_referencia',
        'valor_total',
        'status_pagamento',
    ];

    protected function casts(): array
    {
        return [
            'mes_referencia' => 'date',
            'valor_total' => 'decimal:2',
        ];
    }

    public function cartaoCredito(): BelongsTo
    {
        return $this->belongsTo(CartaoCredito::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }
}
