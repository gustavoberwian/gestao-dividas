<?php

namespace App\Http\Controllers;

use App\Models\CartaoCredito;
use App\Models\Fatura;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FaturaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cartao_credito_id' => ['required', 'integer', 'exists:cartoes_credito,id'],
            'mes_referencia' => ['required', 'date'],
            'valor_total' => ['required', 'numeric', 'min:0'],
        ]);

        $cartaoCredito = CartaoCredito::findOrFail($validated['cartao_credito_id']);
        $this->authorize('view', $cartaoCredito);

        $cartaoCredito->faturas()->create([
            'mes_referencia' => $validated['mes_referencia'],
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
}
