@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('content')
  @include('partials.alert-status')

  <h4 class="mb-4">Dashboard</h4>

  {{-- Cartoes com os numeros do RF008. Graficos (ApexCharts/ChartJS) ficam para a proxima etapa --}}
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
@endsection
