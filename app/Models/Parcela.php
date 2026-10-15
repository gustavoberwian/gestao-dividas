<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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

    /**
     * Situacao calculada na leitura (Bloco F); o banco guarda apenas pendente|paga.
     * Datas no fuso da aplicacao (America/Sao_Paulo).
     *
     * @return 'paga'|'vencida'|'vence_hoje'|'pendente'
     */
    public function situacao(?Carbon $hoje = null): string
    {
        if ($this->status_pagamento === 'paga') {
            return 'paga';
        }

        $hoje = ($hoje ?? Carbon::now())->copy()->startOfDay();
        $vencimento = $this->data_vencimento->copy()->startOfDay();

        return match (true) {
            $vencimento->lt($hoje) => 'vencida',
            $vencimento->eq($hoje) => 'vence_hoje',
            default => 'pendente',
        };
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
