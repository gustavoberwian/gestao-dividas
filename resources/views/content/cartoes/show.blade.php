@extends('layouts/layoutMaster')

@section('title', 'Faturas do Cartão')

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1">{{ $cartao->nome_cartao }}</h4>
      <p class="text-body-secondary mb-0">
        Limite: R$ {{ number_format($cartao->limite, 2, ',', '.') }} ·
        Fechamento dia {{ $cartao->dia_fechamento }} · Vencimento dia {{ $cartao->dia_vencimento }}
      </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarCartao">
        <i class="icon-base ti tabler-pencil me-1"></i> Editar cartão
      </button>
      <x-acao :action="route('cartoes.destroy', $cartao)" method="DELETE" class="btn-outline-danger" :pequeno="false"
        confirmar="Esta ação não pode ser desfeita. O cartão será excluído junto com todas as suas faturas.">
        <i class="icon-base ti tabler-trash me-1"></i> Excluir cartão
      </x-acao>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaFatura">
        <i class="icon-base ti tabler-plus me-1"></i> Nova fatura
      </button>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead>
          <tr>
            <th>Mês de vencimento</th>
            <th>Fechamento</th>
            <th>Vencimento</th>
            <th>Valor</th>
            <th>Situação</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($faturas as $fatura)
            <tr>
              <td>{{ $fatura->mes_referencia->format('m/Y') }}</td>
              <td>{{ $fatura->dataFechamento()->format('d/m/Y') }}</td>
              <td>{{ $fatura->dataVencimento()->format('d/m/Y') }}</td>
              <td>R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</td>
              <td><x-situacao :situacao="$fatura->situacao()" /></td>
              <td class="text-end text-nowrap">
                @if ($fatura->status_pagamento === 'aberta')
                  <x-acao :action="route('faturas.pagar', $fatura)" method="PATCH" class="btn-outline-success">Marcar paga</x-acao>
                @else
                  <x-acao :action="route('cartoes.faturas.reabrir', [$cartao, $fatura])" method="PATCH" class="btn-outline-secondary"
                    confirmar="A fatura voltará a ficar em aberto e a contar na Renda Livre Real.">Desfazer pagamento</x-acao>
                @endif
                <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="modal"
                  data-bs-target="#modalEditarFatura{{ $fatura->id }}" title="Editar fatura" aria-label="Editar fatura">
                  <i class="icon-base ti tabler-pencil"></i>
                </button>
                <x-acao :action="route('cartoes.faturas.destroy', [$cartao, $fatura])" method="DELETE"
                  class="btn-icon btn-text-danger" titulo="Excluir fatura" confirmar="Esta ação não pode ser desfeita.">
                  <i class="icon-base ti tabler-trash"></i>
                </x-acao>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-body-secondary">
                Nenhuma fatura cadastrada ainda.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <x-modal-form id="modalNovaFatura" title="Nova fatura" :action="route('faturas.store')">
    <input type="hidden" name="cartao_credito_id" value="{{ $cartao->id }}">
    <x-campo form="modalNovaFatura" name="mes_referencia" label="Mês de vencimento da fatura" mascara="mes"
      placeholder="mm/aaaa" ajuda="O mês em que a fatura vence (dia {{ $cartao->dia_vencimento }})." />
    <x-campo form="modalNovaFatura" name="valor_total" label="Valor da fatura" mascara="moeda" placeholder="R$ 0,00" />
  </x-modal-form>

  <x-modal-form id="modalEditarCartao" title="Editar cartão" :action="route('cartoes.update', $cartao)" method="PUT">
    <x-campo form="modalEditarCartao" name="nome_cartao" label="Nome do cartão" :value="$cartao->nome_cartao" />
    <x-campo form="modalEditarCartao" name="limite" label="Limite" mascara="moeda" :value="number_format($cartao->limite, 2, ',', '.')" />
    <div class="row">
      <div class="col-6">
        <x-campo form="modalEditarCartao" name="dia_fechamento" label="Dia de fechamento" mascara="inteiro" maxlength="2"
          :value="$cartao->dia_fechamento" />
      </div>
      <div class="col-6">
        <x-campo form="modalEditarCartao" name="dia_vencimento" label="Dia de vencimento" mascara="inteiro" maxlength="2"
          :value="$cartao->dia_vencimento" />
      </div>
    </div>
  </x-modal-form>

  @foreach ($faturas as $fatura)
    <x-modal-form id="modalEditarFatura{{ $fatura->id }}" title="Editar fatura de {{ $fatura->mes_referencia->format('m/Y') }}"
      :action="route('cartoes.faturas.update', [$cartao, $fatura])" method="PUT">
      <x-campo form="modalEditarFatura{{ $fatura->id }}" name="mes_referencia" label="Mês de vencimento da fatura" mascara="mes"
        :value="$fatura->mes_referencia->toDateString()" />
      <x-campo form="modalEditarFatura{{ $fatura->id }}" name="valor_total" label="Valor da fatura" mascara="moeda"
        :value="number_format($fatura->valor_total, 2, ',', '.')" />
    </x-modal-form>
  @endforeach
@endsection
