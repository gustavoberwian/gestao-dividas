@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@php
  $moeda = fn ($valor) => 'R$ '.number_format($valor, 2, ',', '.');
  $haAlertas = ! $rendaCadastrada || $quantidadeParcelasVencidas > 0 || $alertasFaturas->isNotEmpty();
@endphp

@section('content')
  <h4 class="mb-4">Dashboard</h4>

  {{-- Faixa de alertas (some quando nao ha nada) --}}
  @if ($haAlertas)
    <div class="mb-4" id="alertas">
      @unless ($rendaCadastrada)
        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 mb-2" role="alert">
          <i class="icon-base ti tabler-cash"></i>
          <span>Renda mensal não cadastrada. Sem ela não dá para calcular a Renda Livre Real.</span>
          <button type="button" class="btn btn-sm btn-warning ms-auto" data-bs-toggle="modal" data-bs-target="#modalRenda">
            Cadastrar renda
          </button>
        </div>
      @endunless

      @if ($quantidadeParcelasVencidas > 0)
        <div class="alert alert-danger d-flex flex-wrap align-items-center gap-2 mb-2" role="alert">
          <i class="icon-base ti tabler-alert-triangle"></i>
          <span>
            <strong>{{ $quantidadeParcelasVencidas }} {{ $quantidadeParcelasVencidas === 1 ? 'parcela vencida' : 'parcelas vencidas' }}</strong>
            somando {{ $moeda($totalParcelasVencidas) }}.
          </span>
          <a href="{{ route('dividas.index') }}" class="alert-link ms-auto">Ver dívidas</a>
        </div>
      @endif

      @foreach ($alertasFaturas as $alerta)
        <div @class(['alert d-flex flex-wrap align-items-center gap-2 mb-2', 'alert-danger' => $alerta['dias'] < 0, 'alert-warning' => $alerta['dias'] >= 0]) role="alert">
          <i class="icon-base ti tabler-credit-card"></i>
          <span>
            Fatura do cartão <strong>{{ $alerta['cartao'] }}</strong>
            @if ($alerta['dias'] < 0)
              venceu em {{ $alerta['vencimento']->format('d/m/Y') }}.
            @elseif ($alerta['dias'] === 0)
              vence hoje.
            @else
              vence em {{ $alerta['dias'] }} {{ $alerta['dias'] === 1 ? 'dia' : 'dias' }} ({{ $alerta['vencimento']->format('d/m/Y') }}).
            @endif
          </span>
          <a href="{{ $alerta['url'] }}" class="alert-link ms-auto">Ver fatura</a>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Cartoes de resumo --}}
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <p class="text-body-secondary mb-1">Renda mensal</p>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRenda">
              {{ $rendaCadastrada ? 'Editar' : 'Cadastrar' }}
            </button>
          </div>
          <h5 class="mb-0">{{ $moeda($rendaMensal) }}</h5>
          @unless ($rendaCadastrada)
            <small class="text-warning">Cadastre sua renda para calcular a Renda Livre Real.</small>
          @endunless
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Faturas em aberto</p>
          <h5 class="mb-0">{{ $moeda($totalFaturasAbertas) }}</h5>
          <small class="text-body-secondary">Todas as faturas ainda não pagas</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Parcelas do mês</p>
          <h5 class="mb-0">{{ $moeda($totalParcelasDividasDoMes) }}</h5>
          <small class="text-body-secondary">Parcelas de dívidas pendentes que vencem este mês</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div @class(['card h-100 border', 'border-success' => $saldoDisponivel >= 0, 'border-danger' => $saldoDisponivel < 0])>
        <div class="card-body">
          <p class="text-body-secondary mb-1 fw-medium">Renda Livre Real</p>
          <h3 @class(['mb-1', 'text-success' => $saldoDisponivel >= 0, 'text-danger' => $saldoDisponivel < 0])>
            {{ $moeda($saldoDisponivel) }}
          </h3>
          @if ($percentualComprometido !== null)
            <small @class(['d-block', 'text-danger' => $percentualComprometido > 100, 'text-body-secondary' => $percentualComprometido <= 100])>
              {{ number_format($percentualComprometido, 0, ',', '.') }}% da renda comprometida
            </small>
          @endif
          <small class="text-body-secondary">Renda mensal − faturas em aberto − parcelas de dívidas do mês</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    {{-- Para onde vai sua renda --}}
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Para onde vai sua renda este mês</h5>
        </div>
        <div class="card-body" id="barraRenda">
          @if ($barraRenda['vazia'])
            <p class="text-body-secondary mb-0">Cadastre sua renda mensal, faturas ou parcelas para ver a divisão da sua renda.</p>
          @else
            <div class="progress-stacked mb-3" style="height: 1.75rem">
              @foreach ($barraRenda['segmentos'] as $segmento)
                <div class="progress" role="progressbar" style="width: {{ $segmento['largura'] }}%; height: 1.75rem"
                  aria-label="{{ $segmento['rotulo'] }}: {{ $moeda($segmento['valor']) }}"
                  aria-valuenow="{{ $segmento['largura'] }}" aria-valuemin="0" aria-valuemax="100"
                  title="{{ $segmento['rotulo'] }}: {{ $moeda($segmento['valor']) }}">
                  <div class="progress-bar {{ $segmento['cor'] }}"></div>
                </div>
              @endforeach
            </div>
            <ul class="list-inline mb-0">
              @foreach ($barraRenda['segmentos'] as $segmento)
                <li class="list-inline-item me-4 mb-1">
                  <span class="badge badge-dot {{ $segmento['cor'] }} me-1"></span>
                  {{ $segmento['rotulo'] }}: <strong>{{ $moeda($segmento['valor']) }}</strong>
                </li>
              @endforeach
            </ul>
            @if ($barraRenda['excedente'] > 0)
              <p class="text-danger mb-0 mt-2">
                <i class="icon-base ti tabler-alert-circle me-1"></i>
                Comprometido acima da renda em {{ $moeda($barraRenda['excedente']) }}.
              </p>
            @endif
          @endif
        </div>
      </div>
    </div>

    {{-- Proximos vencimentos --}}
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Próximos vencimentos</h5>
          <small class="text-body-secondary">Parcelas e faturas não pagas, vencidas ou dos próximos 30 dias</small>
        </div>
        @if ($proximosVencimentos->isEmpty())
          <div class="card-body">
            <p class="text-body-secondary mb-0">Nada a vencer nos próximos 30 dias.</p>
          </div>
        @else
          <ul class="list-group list-group-flush" id="proximosVencimentos">
            @foreach ($proximosVencimentos->take(8) as $item)
              <li class="list-group-item d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                  <a href="{{ $item['url'] }}" class="text-heading">{{ $item['descricao'] }}</a>
                  <div class="small text-body-secondary">{{ $item['data']->format('d/m/Y') }} · {{ $moeda($item['valor']) }}</div>
                </div>
                @if ($item['dias'] < 0)
                  <span class="badge bg-label-danger">Vencida</span>
                @elseif ($item['dias'] === 0)
                  <span class="badge bg-label-warning">Vence hoje</span>
                @else
                  <span class="badge bg-label-secondary">Em {{ $item['dias'] }} {{ $item['dias'] === 1 ? 'dia' : 'dias' }}</span>
                @endif
              </li>
            @endforeach
          </ul>
          @if ($proximosVencimentos->count() > 8)
            <div class="card-footer small text-body-secondary">
              E mais {{ $proximosVencimentos->count() - 8 }} vencimento(s).
            </div>
          @endif
        @endif
      </div>
    </div>
  </div>

  {{-- Saldo devedor por divida --}}
  <div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h5 class="mb-0">Evolução do saldo devedor</h5>
        <small class="text-body-secondary">
          Cada linha é uma dívida: começa no valor emprestado e mostra quanto você ainda deve ao fim de cada mês.
        </small>
      </div>
      @if ($dividas->count() > 1)
        <select class="form-select form-select-sm w-auto" id="filtroDivida" aria-label="Filtrar dívida">
          <option value="">Todas as dívidas</option>
          @foreach ($dividas as $divida)
            <option value="{{ $divida->id }}">{{ $divida->descricao }}</option>
          @endforeach
        </select>
      @endif
    </div>
    <div class="card-body">
      @if ($dividas->isEmpty())
        <div class="text-center py-5">
          <p class="text-body-secondary">Você ainda não cadastrou nenhuma dívida.</p>
          <a href="{{ route('dividas.index') }}#modalNovaDivida" class="btn btn-primary">Cadastrar dívida</a>
        </div>
      @else
        <div id="saldoDevedorChart"></div>
      @endif
    </div>
  </div>

  <x-modal-form id="modalRenda" title="Renda mensal" :action="route('perfil.renda.atualizar')" method="PUT">
    <x-campo form="modalRenda" name="renda_mensal" label="Renda mensal" mascara="moeda" placeholder="R$ 0,00"
      :value="$rendaCadastrada ? number_format($rendaMensal, 2, ',', '.') : null" />
  </x-modal-form>
@endsection

@php
  $dashboardData = ['seriesSaldoDevedor' => $seriesSaldoDevedor];
@endphp

@section('page-script')
  <script>
    window.dashboardData = @json($dashboardData);
  </script>
  @vite(['resources/assets/js/dashboard.js'])
@endsection
