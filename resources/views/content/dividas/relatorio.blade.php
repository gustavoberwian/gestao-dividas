@extends('layouts/layoutMaster')

@section('title', 'Relatorio de Amortizacao')

@section('content')
  @include('partials.alert-status')

  <div class="mb-4">
    <h4 class="mb-1">{{ $divida->descricao }}</h4>
    <p class="text-body-secondary mb-0">Credor: {{ $divida->credor }}</p>
  </div>

  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Valor principal</p>
          <h5 class="mb-0">R$ {{ number_format($divida->valor_principal, 2, ',', '.') }}</h5>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Saldo devedor atual</p>
          <h5 class="mb-0">R$ {{ number_format($divida->saldo_devedor_atual, 2, ',', '.') }}</h5>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <p class="text-body-secondary mb-1">Data de inicio</p>
          <h5 class="mb-0">{{ $divida->data_inicio->format('d/m/Y') }}</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-7">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Historico de juros</h5>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaTaxa">
            <i class="icon-base ti tabler-plus me-1"></i> Lancar taxa do mes
          </button>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Mes de referencia</th>
                <th>Taxa de juros</th>
                <th>Saldo devedor calculado</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($historico as $registro)
                <tr>
                  <td>{{ $registro->mes_referencia->format('m/Y') }}</td>
                  <td>{{ number_format($registro->taxa_juros * 100, 2, ',', '.') }}%</td>
                  <td>R$ {{ number_format($registro->saldo_devedor_calculado, 2, ',', '.') }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-body-secondary">
                    Nenhuma taxa de juros lancada ainda.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Parcelas</h5>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaParcela">
            <i class="icon-base ti tabler-plus me-1"></i> Nova parcela
          </button>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Nº</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($parcelas as $parcela)
                <tr>
                  <td>{{ $parcela->numero_parcela }}</td>
                  <td>R$ {{ number_format($parcela->valor, 2, ',', '.') }}</td>
                  <td>{{ $parcela->data_vencimento->format('d/m/Y') }}</td>
                  <td>
                    <span class="badge {{ $parcela->status_pagamento === 'paga' ? 'bg-label-success' : 'bg-label-warning' }}">
                      {{ ucfirst($parcela->status_pagamento) }}
                    </span>
                  </td>
                  <td>
                    @if ($parcela->status_pagamento === 'pendente')
                      <form action="{{ route('parcelas.pagar', $parcela) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline-success">Marcar paga</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-body-secondary">
                    Nenhuma parcela cadastrada ainda.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalNovaTaxa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('dividas.juros.store', $divida) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Lancar taxa de juros do mes</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label" for="mes_referencia">Mes de referencia</label>
              <input type="date" id="mes_referencia" name="mes_referencia" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="taxa_juros">Taxa de juros do mes (ex: 0.015 = 1,5%)</label>
              <input type="number" step="0.0001" min="0" id="taxa_juros" name="taxa_juros" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalNovaParcela" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('dividas.parcelas.store', $divida) }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Nova parcela</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label" for="numero_parcela">Numero da parcela</label>
              <input type="number" min="1" id="numero_parcela" name="numero_parcela" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="valor">Valor</label>
              <input type="number" step="0.01" min="0.01" id="valor" name="valor" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="data_vencimento">Data de vencimento</label>
              <input type="date" id="data_vencimento" name="data_vencimento" class="form-control" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
