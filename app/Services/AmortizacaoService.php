<?php

namespace App\Services;

use App\Models\Divida;
use App\Models\HistoricoJuros;
use App\Models\Parcela;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AmortizacaoService
{
    /**
     * Calcula o saldo devedor de uma divida para o mes de referencia informado,
     * persistindo o resultado em HistoricoJuros e atualizando o cache em Divida.
     *
     * Formula: SD_atual = SD_anterior * (1 + i) - P
     */
    public function calcularSaldoDevedor(Divida $divida, string|CarbonInterface $mesReferencia): float
    {
        $mesReferencia = Carbon::parse($mesReferencia)->startOfMonth();

        return DB::transaction(function () use ($divida, $mesReferencia) {
            $sdAnterior = $this->saldoDevedorAnterior($divida, $mesReferencia);
            $taxaJuros = $this->taxaJurosDoMes($divida, $mesReferencia);
            $totalPago = $this->totalPagoNoMes($divida, $mesReferencia);

            $sdAtual = round($sdAnterior * (1 + $taxaJuros) - $totalPago, 2);

            HistoricoJuros::updateOrCreate(
                [
                    'divida_id' => $divida->id,
                    'mes_referencia' => $mesReferencia->toDateString(),
                ],
                [
                    'taxa_juros' => $taxaJuros,
                    'saldo_devedor_calculado' => $sdAtual,
                ]
            );

            $divida->update(['saldo_devedor_atual' => $sdAtual]);

            return $sdAtual;
        });
    }

    private function saldoDevedorAnterior(Divida $divida, CarbonInterface $mesReferencia): float
    {
        $historicoAnterior = HistoricoJuros::where('divida_id', $divida->id)
            ->where('mes_referencia', '<', $mesReferencia->toDateString())
            ->orderByDesc('mes_referencia')
            ->first();

        return $historicoAnterior
            ? (float) $historicoAnterior->saldo_devedor_calculado
            : (float) $divida->valor_principal;
    }

    private function taxaJurosDoMes(Divida $divida, CarbonInterface $mesReferencia): float
    {
        $historicoMesAtual = HistoricoJuros::where('divida_id', $divida->id)
            ->whereDate('mes_referencia', $mesReferencia->toDateString())
            ->first();

        return $historicoMesAtual ? (float) $historicoMesAtual->taxa_juros : 0.0;
    }

    private function totalPagoNoMes(Divida $divida, CarbonInterface $mesReferencia): float
    {
        return (float) Parcela::where('divida_id', $divida->id)
            ->where('status_pagamento', 'paga')
            ->whereYear('data_vencimento', $mesReferencia->year)
            ->whereMonth('data_vencimento', $mesReferencia->month)
            ->sum('valor');
    }
}
