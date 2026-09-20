@extends('layouts/layoutMaster')

@section('title', 'Cartoes de Credito')

@section('content')
  @include('partials.alert-status')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Cartoes de Credito</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCartao">
      <i class="icon-base ti tabler-plus me-1"></i> Novo Cartao
    </button>
  </div>

  <div class="card">
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Limite</th>
            <th>Dia de fechamento</th>
            <th>Dia de vencimento</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($cartoes as $cartao)
            <tr>
              <td>{{ $cartao->nome_cartao }}</td>
              <td>R$ {{ number_format($cartao->limite, 2, ',', '.') }}</td>
              <td>{{ $cartao->dia_fechamento }}</td>
              <td>{{ $cartao->dia_vencimento }}</td>
              <td>
                <a href="{{ route('cartoes.show', $cartao) }}" class="btn btn-sm btn-outline-primary">
                  Ver faturas
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-body-secondary">
                Nenhum cartao cadastrado ainda.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="modalNovoCartao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('cartoes.store') }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Novo Cartao de Credito</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label" for="nome_cartao">Nome do cartao</label>
              <input type="text" id="nome_cartao" name="nome_cartao" class="form-control" value="{{ old('nome_cartao') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="limite">Limite</label>
              <input type="number" step="0.01" min="0" id="limite" name="limite" class="form-control" value="{{ old('limite') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="dia_fechamento">Dia de fechamento (1-31)</label>
              <input type="number" min="1" max="31" id="dia_fechamento" name="dia_fechamento" class="form-control" value="{{ old('dia_fechamento') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="dia_vencimento">Dia de vencimento (1-31)</label>
              <input type="number" min="1" max="31" id="dia_vencimento" name="dia_vencimento" class="form-control" value="{{ old('dia_vencimento') }}" required>
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
