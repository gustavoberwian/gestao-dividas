@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
  @include('partials.alert-status')

  <h4 class="mb-4">Dashboard</h4>

  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="card h-100">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Renda mensal</p>
          <h5 class="mb-0">R$ {{ number_format($rendaMensal, 2, ',', '.') }}</h5>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card h-100">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Faturas em aberto</p>
          <h5 class="mb-0">R$ {{ number_format($totalFaturasAbertas, 2, ',', '.') }}</h5>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card h-100">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Parcelas de dividas do mes</p>
          <h5 class="mb-0">R$ {{ number_format($totalParcelasDividasDoMes, 2, ',', '.') }}</h5>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card h-100 border-primary">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Saldo disponivel</p>
          <h5 class="mb-0 {{ $saldoDisponivel < 0 ? 'text-danger' : 'text-success' }}">
            R$ {{ number_format($saldoDisponivel, 2, ',', '.') }}
          </h5>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Evolucao do saldo devedor</h5>
        </div>
        <div class="card-body">
          <div id="evolucaoSaldoDevedorChart"></div>
        </div>
      </div>
    </div>
    <div class="col-md-5 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Composicao do orcamento do mes</h5>
        </div>
        <div class="card-body">
          <div id="composicaoOrcamentoChart"></div>
        </div>
      </div>
    </div>
  </div>
@endsection

@php
  $dashboardData = [
      'evolucaoSaldoDevedorLabels' => $evolucaoSaldoDevedorLabels,
      'evolucaoSaldoDevedorValores' => $evolucaoSaldoDevedorValores,
      'totalFaturasAbertas' => $totalFaturasAbertas,
      'totalParcelasDividasDoMes' => $totalParcelasDividasDoMes,
      'saldoDisponivel' => $saldoDisponivel,
  ];
@endphp

@section('page-script')
  <script>
    window.dashboardData = @json($dashboardData);
  </script>
  @vite(['resources/assets/js/dashboard.js'])
@endsection
