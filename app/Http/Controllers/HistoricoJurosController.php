<?php

namespace App\Http\Controllers;

use App\Models\Divida;
use App\Models\HistoricoJuros;
use App\Services\AmortizacaoService;
use App\Support\NumeroBr;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoricoJurosController extends Controller
{
    public function store(Request $request, Divida $divida, AmortizacaoService $amortizacaoService): RedirectResponse
    {
        $this->authorize('view', $divida);

        $validated = $request->validate([
            'mes_referencia' => ['required', 'date'],
            'taxa_juros' => $this->regraTaxa($request),
        ]);

        $mesReferencia = Carbon::parse($validated['mes_referencia'])->startOfMonth();

        DB::transaction(function () use ($divida, $mesReferencia, $validated, $amortizacaoService) {
            HistoricoJuros::updateOrCreate(
                [
                    'divida_id' => $divida->id,
                    'mes_referencia' => $mesReferencia->toDateString(),
                ],
                [
                    'taxa_juros' => $this->taxaEmFracao($validated['taxa_juros']),
                    'saldo_devedor_calculado' => 0,
                ]
            );

            // RF007: cadastrar a taxa do mes dispara o recalculo do saldo devedor (e dos meses
            // seguintes, se a taxa for lancada em um mes anterior ao ultimo ja calculado)
            $amortizacaoService->recalcularAPartirDe($divida, $mesReferencia);
        });

        return redirect()->back()->with('status', 'Taxa de juros registrada e saldo devedor recalculado.');
    }

    public function update(Request $request, Divida $divida, HistoricoJuros $historico, AmortizacaoService $amortizacaoService): RedirectResponse
    {
        $this->authorize('update', $historico);

        $validated = $request->validate(['taxa_juros' => $this->regraTaxa($request)]);

        DB::transaction(function () use ($divida, $historico, $validated, $amortizacaoService) {
            $historico->update(['taxa_juros' => $this->taxaEmFracao($validated['taxa_juros'])]);
            $amortizacaoService->recalcularAPartirDe($divida, $historico->mes_referencia);
        });

        return redirect()->back()->with('status', 'Taxa de juros atualizada e saldos recalculados.');
    }

    public function destroy(Divida $divida, HistoricoJuros $historico, AmortizacaoService $amortizacaoService): RedirectResponse
    {
        $this->authorize('delete', $historico);

        $mes = $historico->mes_referencia->copy();

        $mensagem = DB::transaction(function () use ($divida, $historico, $mes, $amortizacaoService) {
            $temPagamentoNoMes = $divida->parcelas()
                ->where('status_pagamento', 'paga')
                ->whereYear('data_vencimento', $mes->year)
                ->whereMonth('data_vencimento', $mes->month)
                ->exists();

            // Com pagamento no mes, o registro fica com 0% para o pagamento continuar
            // abatendo o saldo; sem pagamento, o registro e removido.
            if ($temPagamentoNoMes) {
                $historico->update(['taxa_juros' => 0]);
            } else {
                $historico->delete();
            }

            $amortizacaoService->recalcularAPartirDe($divida, $mes);

            return $temPagamentoNoMes
                ? 'Taxa removida: o mês tem pagamento, então ficou com 0,00%. Saldos recalculados.'
                : 'Taxa de juros excluída e saldos recalculados.';
        });

        return redirect()->back()->with('status', $mensagem);
    }

    /** A taxa e digitada em percentual ("1,5" = 1,5%) e gravada como fracao (0.0150). */
    private function regraTaxa(Request $request): array
    {
        NumeroBr::normalizarCampos($request, ['taxa_juros']);

        return ['required', 'numeric', 'between:0,100', 'decimal:0,2'];
    }

    private function taxaEmFracao(mixed $percentual): float
    {
        return round((float) $percentual / 100, 4);
    }
}
