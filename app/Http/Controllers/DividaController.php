<?php

namespace App\Http\Controllers;

use App\Models\Divida;
use App\Services\AmortizacaoService;
use App\Support\NumeroBr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DividaController extends Controller
{
    public function index(Request $request): View
    {
        $dividas = $request->user()->dividas()
            ->withCount(['parcelas as parcelas_vencidas_count' => function ($query) {
                $query->where('status_pagamento', 'pendente')
                    ->whereDate('data_vencimento', '<', now()->toDateString());
            }])
            ->latest('data_inicio')
            ->get();

        return view('content.dividas.index', compact('dividas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->regras($request));

        $request->user()->dividas()->create([
            ...$validated,
            // saldo devedor inicial = valor principal, ainda sem juros aplicados (secao 3 da SPEC.md)
            'saldo_devedor_atual' => $validated['valor_principal'],
        ]);

        return redirect()->back()->with('status', 'Dívida cadastrada com sucesso.');
    }

    public function update(Request $request, Divida $divida, AmortizacaoService $amortizacaoService): RedirectResponse
    {
        $this->authorize('update', $divida);

        $validated = $request->validate($this->regras($request));

        DB::transaction(function () use ($divida, $validated, $amortizacaoService) {
            $divida->update($validated);

            // o valor principal e o SD inicial: muda-lo exige recalcular todos os meses
            if ($divida->wasChanged('valor_principal')) {
                $amortizacaoService->recalcularAPartirDe($divida);
            }
        });

        return redirect()->back()->with('status', 'Dívida atualizada.');
    }

    public function destroy(Divida $divida): RedirectResponse
    {
        $this->authorize('delete', $divida);

        // parcelas e historico de juros saem junto (cascadeOnDelete)
        DB::transaction(fn () => $divida->delete());

        return redirect()->route('dividas.index')->with('status', 'Dívida excluída, com suas parcelas e histórico de juros.');
    }

    private function regras(Request $request): array
    {
        NumeroBr::normalizarCampos($request, ['valor_principal']);

        return [
            'descricao' => ['required', 'string', 'max:255'],
            'valor_principal' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'credor' => ['required', 'string', 'max:255'],
            'data_inicio' => ['required', 'date'],
        ];
    }

    public function relatorioAmortizacao(Request $request, Divida $divida): View
    {
        $this->authorize('view', $divida);

        $historico = $divida->historicoJuros()->orderBy('mes_referencia')->get();
        $parcelas = $divida->parcelas()->orderBy('data_vencimento')->get();
        $decomposicao = $this->decomposicaoMensal($divida, $historico, $parcelas);

        return view('content.dividas.relatorio', compact('divida', 'historico', 'parcelas', 'decomposicao'));
    }

    /**
     * Quanto do pagamento de cada mes foi juros e quanto amortizou a divida (Bloco J).
     * Apenas exibe o historico ja calculado pelo AmortizacaoService:
     * juros = SD_anterior x i; amortizacao = pago - juros; SD = SD_anterior + juros - pago.
     *
     * @return array<int, array<string, mixed>>
     */
    private function decomposicaoMensal(Divida $divida, $historico, $parcelas): array
    {
        $pagoPorMes = $parcelas->where('status_pagamento', 'paga')
            ->groupBy(fn ($parcela) => $parcela->data_vencimento->format('Y-m'))
            ->map(fn ($grupo) => (float) $grupo->sum('valor'));

        $saldoAnterior = (float) $divida->valor_principal;
        $linhas = [];

        foreach ($historico as $registro) {
            $juros = round($saldoAnterior * (float) $registro->taxa_juros, 2);
            $pago = $pagoPorMes->get($registro->mes_referencia->format('Y-m'), 0.0);

            $linhas[$registro->id] = [
                'mes' => $registro->mes_referencia->format('m/Y'),
                'juros' => $juros,
                'pago' => $pago,
                'amortizacao' => round($pago - $juros, 2),
            ];

            $saldoAnterior = (float) $registro->saldo_devedor_calculado;
        }

        return $linhas;
    }
}
