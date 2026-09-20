<?php

namespace App\Http\Controllers;

use App\Models\Divida;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DividaController extends Controller
{
    public function index(Request $request): View
    {
        $dividas = $request->user()->dividas()->latest('data_inicio')->get();

        return view('content.dividas.index', compact('dividas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'valor_principal' => ['required', 'numeric', 'min:0.01'],
            'credor' => ['required', 'string', 'max:255'],
            'data_inicio' => ['required', 'date'],
        ]);

        $request->user()->dividas()->create([
            ...$validated,
            // saldo devedor inicial = valor principal, ainda sem juros aplicados (secao 3 da SPEC.md)
            'saldo_devedor_atual' => $validated['valor_principal'],
        ]);

        return redirect()->back()->with('status', 'Divida cadastrada com sucesso.');
    }

    public function relatorioAmortizacao(Request $request, Divida $divida): View
    {
        $this->authorize('view', $divida);

        $historico = $divida->historicoJuros()->orderBy('mes_referencia')->get();
        $parcelas = $divida->parcelas()->orderBy('data_vencimento')->get();

        return view('content.dividas.relatorio', compact('divida', 'historico', 'parcelas'));
    }
}
