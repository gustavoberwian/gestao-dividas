<?php

namespace App\Http\Controllers;

use App\Models\CartaoCredito;
use App\Support\NumeroBr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // situacao() e as datas da fatura usam os dias do cartao: evita uma consulta por linha
        $faturas->each->setRelation('cartaoCredito', $cartao);

        return view('content.cartoes.show', compact('cartao', 'faturas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->regras($request));

        $request->user()->cartoesCredito()->create($validated);

        return redirect()->back()->with('status', 'Cartão de crédito cadastrado com sucesso.');
    }

    public function update(Request $request, CartaoCredito $cartao): RedirectResponse
    {
        $this->authorize('update', $cartao);

        $validated = $request->validate($this->regras($request));
        DB::transaction(fn () => $cartao->update($validated));

        return redirect()->back()->with('status', 'Cartão de crédito atualizado.');
    }

    public function destroy(CartaoCredito $cartao): RedirectResponse
    {
        $this->authorize('delete', $cartao);

        // faturas (e parcelas delas) saem junto (cascadeOnDelete)
        DB::transaction(fn () => $cartao->delete());

        return redirect()->route('cartoes.index')->with('status', 'Cartão excluído, com suas faturas.');
    }

    private function regras(Request $request): array
    {
        NumeroBr::normalizarCampos($request, ['limite']);

        return [
            'nome_cartao' => ['required', 'string', 'max:255'],
            'limite' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'dia_fechamento' => ['required', 'integer', 'between:1,31'],
            'dia_vencimento' => ['required', 'integer', 'between:1,31'],
        ];
    }
}
