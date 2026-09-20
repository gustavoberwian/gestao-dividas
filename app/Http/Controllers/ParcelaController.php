<?php

namespace App\Http\Controllers;

use App\Models\Parcela;
use App\Services\AmortizacaoService;
use Illuminate\Http\RedirectResponse;

class ParcelaController extends Controller
{
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
