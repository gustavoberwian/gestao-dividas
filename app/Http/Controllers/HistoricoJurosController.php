<?php

namespace App\Http\Controllers;

use App\Models\Divida;
use App\Models\HistoricoJuros;
use App\Services\AmortizacaoService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HistoricoJurosController extends Controller
{
    public function store(Request $request, Divida $divida, AmortizacaoService $amortizacaoService): RedirectResponse
    {
        $this->authorize('view', $divida);

        $validated = $request->validate([
            'mes_referencia' => ['required', 'date'],
            'taxa_juros' => ['required', 'numeric', 'min:0'],
        ]);

        $mesReferencia = Carbon::parse($validated['mes_referencia'])->startOfMonth();

        HistoricoJuros::updateOrCreate(
            [
                'divida_id' => $divida->id,
                'mes_referencia' => $mesReferencia->toDateString(),
            ],
            [
                'taxa_juros' => $validated['taxa_juros'],
                'saldo_devedor_calculado' => 0,
            ]
        );

        // RF007: cadastrar a taxa do mes dispara o recalculo do saldo devedor
        $amortizacaoService->calcularSaldoDevedor($divida, $mesReferencia);

        return redirect()->back()->with('status', 'Taxa de juros registrada e saldo devedor recalculado.');
    }
}
