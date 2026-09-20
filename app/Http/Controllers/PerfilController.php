<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function atualizarRenda(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'renda_mensal' => ['required', 'numeric', 'min:0'],
        ]);

        $request->user()->update([
            'renda_mensal' => $validated['renda_mensal'],
        ]);

        return redirect()->back()->with('status', 'Renda mensal atualizada com sucesso.');
    }
}
