<?php

namespace App\Http\Controllers;

use App\Models\Divida;
use App\Models\Fatura;
use App\Models\Parcela;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /** Janela da lista "Próximos vencimentos" e do alerta de fatura a vencer. */
    private const DIAS_PROXIMOS_VENCIMENTOS = 30;

    private const DIAS_ALERTA_FATURA = 7;

    public function index(Request $request): View
    {
        $user = $request->user();
        $hoje = Carbon::today();

        $totalFaturasAbertas = (float) Fatura::whereHas('cartaoCredito', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status_pagamento', 'aberta')->sum('valor_total');

        // Parcelas de dividas (emprestimos) nao tem um agregador como a fatura do
        // cartao, entao entram somadas aqui para nao ficarem de fora do saldo.
        $totalParcelasDividasDoMes = (float) Parcela::whereHas('divida', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status_pagamento', 'pendente')
            ->whereYear('data_vencimento', $hoje->year)
            ->whereMonth('data_vencimento', $hoje->month)
            ->sum('valor');

        // Renda Livre Real = renda - faturas nao pagas - parcelas pendentes de dividas no mes
        $rendaMensal = (float) ($user->renda_mensal ?? 0);
        $saldoDisponivel = $rendaMensal - $totalFaturasAbertas - $totalParcelasDividasDoMes;
        $comprometido = $totalFaturasAbertas + $totalParcelasDividasDoMes;

        $parcelasPendentes = $this->parcelasPendentesAte($user, $hoje->copy()->addDays(self::DIAS_PROXIMOS_VENCIMENTOS));
        $faturasNaoPagas = $this->faturasNaoPagas($user);

        // Parcelas vencidas de meses anteriores nao entram na formula (Bloco F); sao
        // mostradas em um alerta separado.
        $parcelasVencidas = $parcelasPendentes->filter(fn (Parcela $p) => $p->situacao($hoje) === 'vencida');

        return view('dashboard', [
            'rendaMensal' => $rendaMensal,
            'rendaCadastrada' => $user->renda_mensal !== null,
            'totalFaturasAbertas' => $totalFaturasAbertas,
            'totalParcelasDividasDoMes' => $totalParcelasDividasDoMes,
            'saldoDisponivel' => $saldoDisponivel,
            'percentualComprometido' => $rendaMensal > 0 ? $comprometido / $rendaMensal * 100 : null,
            'quantidadeParcelasVencidas' => $parcelasVencidas->count(),
            'totalParcelasVencidas' => (float) $parcelasVencidas->sum('valor'),
            'alertasFaturas' => $this->alertasFaturas($faturasNaoPagas, $hoje),
            'barraRenda' => $this->barraRenda($rendaMensal, $totalFaturasAbertas, $totalParcelasDividasDoMes),
            'proximosVencimentos' => $this->proximosVencimentos($parcelasPendentes, $faturasNaoPagas, $hoje),
            'dividas' => $user->dividas()->orderBy('descricao')->get(['id', 'descricao']),
            'seriesSaldoDevedor' => $this->seriesSaldoDevedor($user),
        ]);
    }

    /** Parcelas de divida pendentes com vencimento ate a data limite (inclui as vencidas). */
    private function parcelasPendentesAte(User $user, Carbon $limite): Collection
    {
        return Parcela::with('divida:id,descricao')
            ->whereHas('divida', fn ($query) => $query->where('user_id', $user->id))
            ->where('status_pagamento', 'pendente')
            ->whereDate('data_vencimento', '<=', $limite->toDateString())
            ->orderBy('data_vencimento')
            ->get();
    }

    private function faturasNaoPagas(User $user): Collection
    {
        return Fatura::with('cartaoCredito')
            ->whereHas('cartaoCredito', fn ($query) => $query->where('user_id', $user->id))
            ->where('status_pagamento', 'aberta')
            ->get();
    }

    /** Faturas vencidas ou que vencem nos proximos dias, para a faixa de alertas. */
    private function alertasFaturas(Collection $faturas, Carbon $hoje): Collection
    {
        return $faturas
            ->map(function (Fatura $fatura) use ($hoje) {
                $dias = (int) $hoje->diffInDays($fatura->dataVencimento(), false);

                return [
                    'cartao' => $fatura->cartaoCredito->nome_cartao,
                    'url' => route('cartoes.show', $fatura->cartao_credito_id),
                    'dias' => $dias,
                    'vencimento' => $fatura->dataVencimento(),
                ];
            })
            ->filter(fn ($alerta) => $alerta['dias'] <= self::DIAS_ALERTA_FATURA)
            ->sortBy('dias')
            ->values();
    }

    /**
     * Segmentos da barra "Para onde vai sua renda este mes", em % da largura.
     * Se o comprometido passar da renda, a barra representa o comprometido e a parte
     * acima da renda aparece como excedente.
     */
    private function barraRenda(float $renda, float $faturas, float $parcelas): array
    {
        $comprometido = $faturas + $parcelas;
        $base = max($renda, $comprometido);

        if ($base <= 0) {
            return ['vazia' => true];
        }

        $faturasDentro = min($faturas, $renda);
        $parcelasDentro = min($parcelas, max($renda - $faturasDentro, 0));
        $excedente = max($comprometido - $renda, 0);
        $livre = max($renda - $comprometido, 0);

        $pct = fn (float $valor) => round($valor / $base * 100, 2);

        return [
            'vazia' => false,
            'segmentos' => array_values(array_filter([
                ['rotulo' => 'Faturas', 'valor' => $faturasDentro, 'largura' => $pct($faturasDentro), 'cor' => 'bg-warning'],
                ['rotulo' => 'Parcelas', 'valor' => $parcelasDentro, 'largura' => $pct($parcelasDentro), 'cor' => 'bg-danger opacity-75'],
                ['rotulo' => 'Renda livre', 'valor' => $livre, 'largura' => $pct($livre), 'cor' => 'bg-success'],
                ['rotulo' => 'Acima da renda', 'valor' => $excedente, 'largura' => $pct($excedente), 'cor' => 'bg-danger progress-bar-striped'],
            ], fn ($segmento) => $segmento['valor'] > 0)),
            'excedente' => $excedente,
        ];
    }

    /** Parcelas e faturas nao pagas ate 30 dias a frente (inclui vencidas), por data. */
    private function proximosVencimentos(Collection $parcelas, Collection $faturas, Carbon $hoje): Collection
    {
        $limite = $hoje->copy()->addDays(self::DIAS_PROXIMOS_VENCIMENTOS);

        $itensParcelas = $parcelas->map(fn (Parcela $parcela) => [
            'descricao' => $parcela->divida->descricao.' — parcela '.$parcela->numero_parcela,
            'tipo' => 'Parcela',
            'data' => $parcela->data_vencimento,
            'valor' => (float) $parcela->valor,
            'url' => route('dividas.relatorio', $parcela->divida_id),
        ]);

        $itensFaturas = $faturas
            ->filter(fn (Fatura $fatura) => $fatura->dataVencimento()->lte($limite))
            ->map(fn (Fatura $fatura) => [
                'descricao' => 'Fatura '.$fatura->cartaoCredito->nome_cartao.' — '.$fatura->mes_referencia->format('m/Y'),
                'tipo' => 'Fatura',
                'data' => $fatura->dataVencimento(),
                'valor' => (float) $fatura->valor_total,
                'url' => route('cartoes.show', $fatura->cartao_credito_id),
            ]);

        return $itensParcelas->concat($itensFaturas)
            ->map(fn ($item) => $item + ['dias' => (int) $hoje->diffInDays($item['data'], false)])
            ->sortBy(fn ($item) => $item['data']->timestamp)
            ->values();
    }

    /**
     * Uma serie por divida para o grafico de saldo devedor: ponto inicial no valor
     * principal (data de inicio) e um ponto no fim de cada mes calculado, com a taxa e o
     * total pago no mes para o tooltip. Somente leitura do historico ja persistido.
     */
    private function seriesSaldoDevedor(User $user): Collection
    {
        return $user->dividas()
            ->with([
                'historicoJuros' => fn ($query) => $query->orderBy('mes_referencia'),
                'parcelas' => fn ($query) => $query->where('status_pagamento', 'paga'),
            ])
            ->orderBy('descricao')
            ->get()
            ->map(function (Divida $divida) {
                $pagoPorMes = $divida->parcelas
                    ->groupBy(fn (Parcela $parcela) => $parcela->data_vencimento->format('Y-m'))
                    ->map(fn ($parcelas) => (float) $parcelas->sum('valor'));

                $pontos = collect([[
                    'x' => $divida->data_inicio->toDateString(),
                    'y' => (float) $divida->valor_principal,
                    'inicial' => true,
                ]]);

                foreach ($divida->historicoJuros as $registro) {
                    $pontos->push([
                        'x' => $registro->mes_referencia->copy()->endOfMonth()->toDateString(),
                        'y' => (float) $registro->saldo_devedor_calculado,
                        'mes' => $registro->mes_referencia->format('m/Y'),
                        'taxa' => round((float) $registro->taxa_juros * 100, 2),
                        'pago' => $pagoPorMes->get($registro->mes_referencia->format('Y-m'), 0.0),
                    ]);
                }

                return ['id' => $divida->id, 'name' => $divida->descricao, 'data' => $pontos->values()];
            });
    }
}
