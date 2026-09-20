<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CartaoCredito extends Model
{
    use HasFactory;

    protected $table = 'cartoes_credito';

    protected $fillable = [
        'user_id',
        'nome_cartao',
        'limite',
        'dia_fechamento',
        'dia_vencimento',
    ];

    protected function casts(): array
    {
        return [
            'limite' => 'decimal:2',
            'dia_fechamento' => 'integer',
            'dia_vencimento' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function faturas(): HasMany
    {
        return $this->hasMany(Fatura::class);
    }
}
