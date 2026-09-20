@extends('layouts/layoutMaster')

@section('title', 'Faturas do Cartao')

@section('content')
  @include('partials.alert-status')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">{{ $cartao->nome_cartao }}</h4>
      <p class="text-body-secondary mb-0">
        Limite: R$ {{ number_format($cartao->limite, 2, ',', '.') }} ·
        Fechamento dia {{ $cartao->dia_fechamento }} · Vencimento dia {{ $cartao->dia_vencimento }}
      </p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaFatura">
      <i class="icon-base ti tabler-plus me-1"></i> Nova Fatura
    </button>
  </div>

  <div class="card">
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Mes de referencia</th>
            <th>Valor total</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($faturas as $fatura)
            <tr>
              <td>{{ $fatura->mes_referencia->format('m/Y') }}</td>
              <td>R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</td>
              <td>
                <span class="badge {{ $fatura->status_pagamento === 'paga' ? 'bg-label-success' : 'bg-label-warning' }}">
                  {{ ucfirst($fatura->status_pagamento) }}
                </span>
              </td>
              <td>
                @if ($fatura->status_pagamento === 'aberta')
                  <form action="{{ route('faturas.pagar', $fatura) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-sm btn-outline-success">Marcar paga</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-body-secondary">
                Nenhuma fatura cadastrada ainda.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="modalNovaFatura" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('faturas.store') }}" method="POST">
          @csrf
          <input type="hidden" name="cartao_credito_id" value="{{ $cartao->id }}">
          <div class="modal-header">
            <h5 class="modal-title">Nova Fatura</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label" for="mes_referencia">Mes de referencia</label>
              <input type="date" id="mes_referencia" name="mes_referencia" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="valor_total">Valor total</label>
              <input type="number" step="0.01" min="0" id="valor_total" name="valor_total" class="form-control" required>
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
