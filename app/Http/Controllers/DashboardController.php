<?php

namespace App\Http\Controllers;

use App\Models\Fatura;
use App\Models\HistoricoJuros;
use App\Models\Parcela;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hoje = Carbon::now();

        $totalFaturasAbertas = Fatura::whereHas('cartaoCredito', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status_pagamento', 'aberta')->sum('valor_total');

        // Parcelas de dividas (emprestimos) nao tem um agregador como a fatura do
        // cartao, entao entram somadas aqui para nao ficarem de fora do saldo.
        $totalParcelasDividasDoMes = Parcela::whereHas('divida', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status_pagamento', 'pendente')
            ->whereYear('data_vencimento', $hoje->year)
            ->whereMonth('data_vencimento', $hoje->month)
            ->sum('valor');

        $rendaMensal = (float) ($user->renda_mensal ?? 0);
        $saldoDisponivel = $rendaMensal - (float) $totalFaturasAbertas - (float) $totalParcelasDividasDoMes;

        // Evolucao do saldo devedor total (soma de todas as dividas do usuario por mes),
        // reaproveitando o historico que o AmortizacaoService ja persiste (RF007/RF009).
        $evolucaoSaldoDevedor = HistoricoJuros::whereHas('divida', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->selectRaw('mes_referencia, SUM(saldo_devedor_calculado) as total')
            ->groupBy('mes_referencia')
            ->orderBy('mes_referencia')
            ->get();

        return view('dashboard', [
            'rendaMensal' => $rendaMensal,
            'totalFaturasAbertas' => (float) $totalFaturasAbertas,
            'totalParcelasDividasDoMes' => (float) $totalParcelasDividasDoMes,
            'saldoDisponivel' => $saldoDisponivel,
            'evolucaoSaldoDevedorLabels' => $evolucaoSaldoDevedor->map(
                fn ($registro) => $registro->mes_referencia->format('m/Y')
            ),
            'evolucaoSaldoDevedorValores' => $evolucaoSaldoDevedor->map(
                fn ($registro) => round((float) $registro->total, 2)
            ),
        ]);
    }
}
