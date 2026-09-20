<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoJuros extends Model
{
    use HasFactory;

    protected $table = 'historico_juros';

    protected $fillable = [
        'divida_id',
        'mes_referencia',
        'taxa_juros',
        'saldo_devedor_calculado',
    ];

    protected function casts(): array
    {
        return [
            'mes_referencia' => 'date',
            'taxa_juros' => 'decimal:4',
            'saldo_devedor_calculado' => 'decimal:2',
        ];
    }

    public function divida(): BelongsTo
    {
        return $this->belongsTo(Divida::class);
    }
}
