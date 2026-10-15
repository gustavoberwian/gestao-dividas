<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    /**
     * Data de vencimento: dia_vencimento do cartao no mes de referencia (que e o mes do
     * vencimento), limitado ao ultimo dia do mes.
     */
    public function dataVencimento(): Carbon
    {
        return self::diaNoMes($this->mes_referencia, $this->cartaoCredito->dia_vencimento);
    }

    /**
     * Data de fechamento: dia_fechamento no mesmo mes se for antes do vencimento, senao no
     * mes anterior; limitado ao ultimo dia do mes.
     */
    public function dataFechamento(): Carbon
    {
        $cartao = $this->cartaoCredito;
        $mes = $cartao->dia_fechamento < $cartao->dia_vencimento
            ? $this->mes_referencia
            : $this->mes_referencia->copy()->startOfMonth()->subMonthNoOverflow();

        return self::diaNoMes($mes, $cartao->dia_fechamento);
    }

    /**
     * Situacao calculada na leitura (Bloco F); o banco guarda apenas aberta|paga.
     *
     * @return 'paga'|'vencida'|'fechada'|'aberta'
     */
    public function situacao(?Carbon $hoje = null): string
    {
        if ($this->status_pagamento === 'paga') {
            return 'paga';
        }

        $hoje = ($hoje ?? Carbon::now())->copy()->startOfDay();

        return match (true) {
            $hoje->gt($this->dataVencimento()) => 'vencida',
            $hoje->gt($this->dataFechamento()) => 'fechada',
            default => 'aberta',
        };
    }

    private static function diaNoMes(Carbon $mes, int $dia): Carbon
    {
        $inicio = $mes->copy()->startOfMonth();

        return $inicio->setDay(min($dia, $inicio->daysInMonth))->startOfDay();
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
