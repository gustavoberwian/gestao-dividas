@extends('layouts/layoutMaster')

@section('title', 'Cartões de Crédito')

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Cartões de Crédito</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoCartao">
      <i class="icon-base ti tabler-plus me-1"></i> Novo Cartão
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
                <x-acao :action="route('cartoes.destroy', $cartao)" method="DELETE" class="btn-icon btn-text-danger"
                  titulo="Excluir cartão" confirmar="Esta ação não pode ser desfeita. O cartão será excluído junto com todas as suas faturas.">
                  <i class="icon-base ti tabler-trash"></i>
                </x-acao>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-body-secondary">
                Nenhum cartão cadastrado ainda.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <x-modal-form id="modalNovoCartao" title="Novo cartão de crédito" :action="route('cartoes.store')">
    <x-campo form="modalNovoCartao" name="nome_cartao" label="Nome do cartão" placeholder="Ex.: Nubank" />
    <x-campo form="modalNovoCartao" name="limite" label="Limite" mascara="moeda" placeholder="R$ 0,00" />
    <div class="row">
      <div class="col-6">
        <x-campo form="modalNovoCartao" name="dia_fechamento" label="Dia de fechamento" mascara="inteiro" placeholder="1 a 31" maxlength="2" />
      </div>
      <div class="col-6">
        <x-campo form="modalNovoCartao" name="dia_vencimento" label="Dia de vencimento" mascara="inteiro" placeholder="1 a 31" maxlength="2" />
      </div>
    </div>
  </x-modal-form>
@endsection
