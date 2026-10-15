<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Divida extends Model
{
    use HasFactory;

    protected $table = 'dividas';

    protected $fillable = [
        'user_id',
        'descricao',
        'valor_principal',
        'credor',
        'data_inicio',
        'saldo_devedor_atual',
    ];

    protected function casts(): array
    {
        return [
            'valor_principal' => 'decimal:2',
            'saldo_devedor_atual' => 'decimal:2',
            'data_inicio' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class);
    }

    public function historicoJuros(): HasMany
    {
        return $this->hasMany(HistoricoJuros::class);
    }

    /**
     * Alias usado pelo scoped binding das rotas /dividas/{divida}/juros/{historico}:
     * o Laravel procura a relacao pelo plural do parametro ("historicos").
     */
    public function historicos(): HasMany
    {
        return $this->historicoJuros();
    }
}
