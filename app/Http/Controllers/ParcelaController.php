<?php

namespace App\Http\Controllers;

use App\Models\Divida;
use App\Models\Parcela;
use App\Services\AmortizacaoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParcelaController extends Controller
{
    /**
     * Cadastro de parcela avulsa de uma divida. Nao consta na tabela RF da SPEC.md
     * (secao 4), mas sem ela nao ha como popular parcelas para testar o RF006.
     */
    public function store(Request $request, Divida $divida): RedirectResponse
    {
        $this->authorize('view', $divida);

        $validated = $request->validate([
            'numero_parcela' => ['required', 'integer', 'min:1'],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'data_vencimento' => ['required', 'date'],
        ]);

        $divida->parcelas()->create([
            ...$validated,
            'status_pagamento' => 'pendente',
        ]);

        return redirect()->back()->with('status', 'Parcela cadastrada com sucesso.');
    }

    public function marcarPaga(Parcela $parcela, AmortizacaoService $amortizacaoService): RedirectResponse
    {
        $this->authorize('update', $parcela);

        $parcela->update(['status_pagamento' => 'paga']);

        // RF007: parcela de divida marcada como paga dispara o recalculo do saldo devedor
        if ($parcela->divida_id !== null) {
            $amortizacaoService->calcularSaldoDevedor($parcela->divida, $parcela->data_vencimento);
        }

        return redirect()->back()->with('status', 'Parcela marcada como paga.');
    }
}
