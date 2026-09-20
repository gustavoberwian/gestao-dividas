<?php

namespace App\Http\Controllers;

use App\Models\CartaoCredito;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartaoCreditoController extends Controller
{
    public function index(Request $request): View
    {
        $cartoes = $request->user()->cartoesCredito()->latest()->get();

        return view('content.cartoes.index', compact('cartoes'));
    }

    public function show(Request $request, CartaoCredito $cartao): View
    {
        $this->authorize('view', $cartao);

        $faturas = $cartao->faturas()->orderByDesc('mes_referencia')->get();

        return view('content.cartoes.show', compact('cartao', 'faturas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome_cartao' => ['required', 'string', 'max:255'],
            'limite' => ['required', 'numeric', 'min:0'],
            'dia_fechamento' => ['required', 'integer', 'between:1,31'],
            'dia_vencimento' => ['required', 'integer', 'between:1,31'],
        ]);

        $request->user()->cartoesCredito()->create($validated);

        return redirect()->back()->with('status', 'Cartao de credito cadastrado com sucesso.');
    }
}
