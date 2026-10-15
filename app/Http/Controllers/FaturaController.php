<?php

namespace App\Http\Controllers;

use App\Models\CartaoCredito;
use App\Models\Fatura;
use App\Support\NumeroBr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FaturaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        NumeroBr::normalizarCampos($request, ['valor_total']);

        $validated = $request->validate([
            'cartao_credito_id' => ['required', 'integer', 'exists:cartoes_credito,id'],
            ...$this->regras(),
        ]);

        $cartaoCredito = CartaoCredito::findOrFail($validated['cartao_credito_id']);
        $this->authorize('view', $cartaoCredito);

        $cartaoCredito->faturas()->create([
            'mes_referencia' => Carbon::parse($validated['mes_referencia'])->startOfMonth()->toDateString(),
            'valor_total' => $validated['valor_total'],
        ]);

        return redirect()->back()->with('status', 'Fatura cadastrada com sucesso.');
    }

    public function marcarPaga(Fatura $fatura): RedirectResponse
    {
        $this->authorize('view', $fatura);

        $fatura->update(['status_pagamento' => 'paga']);

        return redirect()->back()->with('status', 'Fatura marcada como paga.');
    }

    public function update(Request $request, CartaoCredito $cartao, Fatura $fatura): RedirectResponse
    {
        $this->authorize('update', $fatura);

        NumeroBr::normalizarCampos($request, ['valor_total']);
        $validated = $request->validate($this->regras());

        DB::transaction(fn () => $fatura->update([
            'mes_referencia' => Carbon::parse($validated['mes_referencia'])->startOfMonth()->toDateString(),
            'valor_total' => $validated['valor_total'],
        ]));

        return redirect()->back()->with('status', 'Fatura atualizada.');
    }

    public function destroy(CartaoCredito $cartao, Fatura $fatura): RedirectResponse
    {
        $this->authorize('delete', $fatura);

        // parcelas ligadas a fatura saem junto (cascadeOnDelete)
        DB::transaction(fn () => $fatura->delete());

        return redirect()->back()->with('status', 'Fatura excluída.');
    }

    public function reabrir(CartaoCredito $cartao, Fatura $fatura): RedirectResponse
    {
        $this->authorize('update', $fatura);

        DB::transaction(fn () => $fatura->update(['status_pagamento' => 'aberta']));

        return redirect()->back()->with('status', 'Pagamento da fatura desfeito.');
    }

    private function regras(): array
    {
        return [
            'mes_referencia' => ['required', 'date'],
            'valor_total' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
        ];
    }
}
