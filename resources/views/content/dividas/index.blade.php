@extends('layouts/layoutMaster')

@section('title', 'Minhas Dividas')

@section('content')
  @include('partials.alert-status')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Minhas Dividas</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaDivida">
      <i class="icon-base ti tabler-plus me-1"></i> Nova Divida
    </button>
  </div>

  <div class="card">
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Descricao</th>
            <th>Credor</th>
            <th>Valor principal</th>
            <th>Saldo devedor atual</th>
            <th>Data de inicio</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($dividas as $divida)
            <tr>
              <td>{{ $divida->descricao }}</td>
              <td>{{ $divida->credor }}</td>
              <td>R$ {{ number_format($divida->valor_principal, 2, ',', '.') }}</td>
              <td>R$ {{ number_format($divida->saldo_devedor_atual, 2, ',', '.') }}</td>
              <td>{{ $divida->data_inicio->format('d/m/Y') }}</td>
              <td>
                <a href="{{ route('dividas.relatorio', $divida) }}" class="btn btn-sm btn-outline-primary">
                  Ver relatorio
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-body-secondary">
                Nenhuma divida cadastrada ainda.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="modalNovaDivida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="{{ route('dividas.store') }}" method="POST">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Nova Divida</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label" for="descricao">Descricao</label>
              <input type="text" id="descricao" name="descricao" class="form-control" value="{{ old('descricao') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="credor">Credor</label>
              <input type="text" id="credor" name="credor" class="form-control" value="{{ old('credor') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="valor_principal">Valor principal</label>
              <input type="number" step="0.01" min="0.01" id="valor_principal" name="valor_principal" class="form-control" value="{{ old('valor_principal') }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="data_inicio">Data de inicio</label>
              <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="{{ old('data_inicio') }}" required>
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
