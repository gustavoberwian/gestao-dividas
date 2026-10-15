@extends('layouts/layoutMaster')

@section('title', 'Minhas Dívidas')

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Minhas Dívidas</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaDivida">
      <i class="icon-base ti tabler-plus me-1"></i> Nova Dívida
    </button>
  </div>

  <div class="card">
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Descrição</th>
            <th>Credor</th>
            <th>Valor principal</th>
            <th>Saldo devedor atual</th>
            <th>Data de início</th>
            <th>Parcelas vencidas</th>
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
                @if ($divida->parcelas_vencidas_count > 0)
                  <span class="badge bg-label-danger">{{ $divida->parcelas_vencidas_count }} {{ $divida->parcelas_vencidas_count === 1 ? 'vencida' : 'vencidas' }}</span>
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
              <td>
                <a href="{{ route('dividas.relatorio', $divida) }}" class="btn btn-sm btn-outline-primary">
                  Ver relatório
                </a>
                <x-acao :action="route('dividas.destroy', $divida)" method="DELETE" class="btn-icon btn-text-danger"
                  titulo="Excluir dívida"
                  confirmar="Esta ação não pode ser desfeita. A dívida será excluída junto com todas as suas parcelas e o histórico de juros.">
                  <i class="icon-base ti tabler-trash"></i>
                </x-acao>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-body-secondary">
                Nenhuma dívida cadastrada ainda.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <x-modal-form id="modalNovaDivida" title="Nova dívida" :action="route('dividas.store')">
    <x-campo form="modalNovaDivida" name="descricao" label="Descrição" placeholder="Ex.: Empréstimo pessoal" />
    <x-campo form="modalNovaDivida" name="credor" label="Credor" placeholder="Ex.: Banco X" />
    <x-campo form="modalNovaDivida" name="valor_principal" label="Valor principal" mascara="moeda" placeholder="R$ 0,00" />
    <x-campo form="modalNovaDivida" name="data_inicio" label="Data de início" mascara="data" placeholder="dd/mm/aaaa" />
  </x-modal-form>
@endsection
