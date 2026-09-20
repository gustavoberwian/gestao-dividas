<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartaoCreditoController extends Controller
{
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
