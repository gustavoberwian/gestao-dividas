@extends('layouts/layoutMaster')

@section('title', 'Relatório de Amortização')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')

  @php
    $moeda = fn ($valor) => 'R$ '.number_format($valor, 2, ',', '.');
    $avisoCascata = 'Os saldos dos meses seguintes serão recalculados.';
  @endphp

  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
      <h4 class="mb-1">{{ $divida->descricao }}</h4>
      <p class="text-body-secondary mb-0">Credor: {{ $divida->credor }}</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarDivida">
        <i class="icon-base ti tabler-pencil me-1"></i> Editar dívida
      </button>
      <x-acao :action="route('dividas.destroy', $divida)" method="DELETE" class="btn-outline-danger" :pequeno="false"
        confirmar="Esta ação não pode ser desfeita. A dívida será excluída junto com todas as suas parcelas e o histórico de juros.">
        <i class="icon-base ti tabler-trash me-1"></i> Excluir dívida
      </x-acao>
    </div>
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
          <p class="text-body-secondary mb-1">Data de início</p>
          <h5 class="mb-0">{{ $divida->data_inicio->format('d/m/Y') }}</h5>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Quanto do que você pagou foi só juros</h5>
      <small class="text-body-secondary">
        Em cada mês, a parte vermelha são os juros cobrados; a verde é o que de fato reduziu a dívida (pagamento − juros).
      </small>
    </div>
    <div class="card-body">
      @if (empty($decomposicao))
        <p class="text-body-secondary mb-0">Lance a taxa de juros de um mês para ver este gráfico.</p>
      @else
        <div id="jurosAmortizacaoChart"></div>
      @endif
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Histórico de juros</h5>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaTaxa">
            <i class="icon-base ti tabler-plus me-1"></i> Lançar taxa do mês
          </button>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead>
              <tr>
                <th>Mês de referência</th>
                <th>Taxa de juros</th>
                <th>Juros do mês</th>
                <th>Pago no mês</th>
                <th>Amortização</th>
                <th>Saldo devedor</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($historico as $registro)
                <tr>
                  <td>{{ $registro->mes_referencia->format('m/Y') }}</td>
                  <td>{{ number_format($registro->taxa_juros * 100, 2, ',', '.') }}%</td>
                  <td class="text-danger">R$ {{ number_format($decomposicao[$registro->id]['juros'], 2, ',', '.') }}</td>
                  <td>R$ {{ number_format($decomposicao[$registro->id]['pago'], 2, ',', '.') }}</td>
                  <td @class(['text-success' => $decomposicao[$registro->id]['amortizacao'] > 0, 'text-danger' => $decomposicao[$registro->id]['amortizacao'] < 0])>
                    R$ {{ number_format($decomposicao[$registro->id]['amortizacao'], 2, ',', '.') }}
                  </td>
                  <td>R$ {{ number_format($registro->saldo_devedor_calculado, 2, ',', '.') }}</td>
                  <td class="text-end text-nowrap">
                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="modal"
                      data-bs-target="#modalEditarTaxa{{ $registro->id }}" title="Editar taxa" aria-label="Editar taxa">
                      <i class="icon-base ti tabler-pencil"></i>
                    </button>
                    <x-acao :action="route('dividas.juros.destroy', [$divida, $registro])" method="DELETE"
                      class="btn-icon btn-text-danger" titulo="Excluir taxa"
                      confirmar="Esta ação não pode ser desfeita. Se o mês tiver pagamento, a taxa fica 0,00%. {{ $avisoCascata }}">
                      <i class="icon-base ti tabler-trash"></i>
                    </x-acao>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-body-secondary">
                    Nenhuma taxa de juros lançada ainda.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12">
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
                <th>Situação</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($parcelas as $parcela)
                <tr>
                  <td>{{ $parcela->numero_parcela }}</td>
                  <td>R$ {{ number_format($parcela->valor, 2, ',', '.') }}</td>
                  <td>{{ $parcela->data_vencimento->format('d/m/Y') }}</td>
                  <td><x-situacao :situacao="$parcela->situacao()" /></td>
                  <td class="text-end text-nowrap">
                    @if ($parcela->status_pagamento === 'pendente')
                      <x-acao :action="route('parcelas.pagar', $parcela)" method="PATCH" class="btn-outline-success">
                        Marcar paga
                      </x-acao>
                    @else
                      <x-acao :action="route('dividas.parcelas.reabrir', [$divida, $parcela])" method="PATCH"
                        class="btn-outline-secondary" confirmar="A parcela voltará a ficar pendente. {{ $avisoCascata }}">
                        Desfazer pagamento
                      </x-acao>
                    @endif
                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary" data-bs-toggle="modal"
                      data-bs-target="#modalEditarParcela{{ $parcela->id }}" title="Editar parcela" aria-label="Editar parcela">
                      <i class="icon-base ti tabler-pencil"></i>
                    </button>
                    <x-acao :action="route('dividas.parcelas.destroy', [$divida, $parcela])" method="DELETE"
                      class="btn-icon btn-text-danger" titulo="Excluir parcela"
                      confirmar="Esta ação não pode ser desfeita.{{ $parcela->status_pagamento === 'paga' ? ' '.$avisoCascata : '' }}">
                      <i class="icon-base ti tabler-trash"></i>
                    </x-acao>
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

  <x-modal-form id="modalNovaTaxa" title="Lançar taxa de juros do mês" :action="route('dividas.juros.store', $divida)">
    <x-campo form="modalNovaTaxa" name="mes_referencia" label="Mês de referência" mascara="mes" placeholder="mm/aaaa" />
    <x-campo form="modalNovaTaxa" name="taxa_juros" label="Taxa de juros do mês (%)" mascara="percentual" sufixo="%"
      placeholder="Ex.: 1,5" />
  </x-modal-form>

  <x-modal-form id="modalNovaParcela" title="Nova parcela" :action="route('dividas.parcelas.store', $divida)">
    <x-campo form="modalNovaParcela" name="numero_parcela" label="Número da parcela" mascara="inteiro"
      :value="$parcelas->max('numero_parcela') + 1" />
    <x-campo form="modalNovaParcela" name="valor" label="Valor" mascara="moeda" placeholder="R$ 0,00" />
    <x-campo form="modalNovaParcela" name="data_vencimento" label="Data de vencimento" mascara="data" placeholder="dd/mm/aaaa" />
  </x-modal-form>

  <x-modal-form id="modalEditarDivida" title="Editar dívida" :action="route('dividas.update', $divida)" method="PUT">
    <x-campo form="modalEditarDivida" name="descricao" label="Descrição" :value="$divida->descricao" />
    <x-campo form="modalEditarDivida" name="credor" label="Credor" :value="$divida->credor" />
    <x-campo form="modalEditarDivida" name="valor_principal" label="Valor principal" mascara="moeda"
      :value="number_format($divida->valor_principal, 2, ',', '.')" ajuda="Alterar o valor principal recalcula todos os meses." />
    <x-campo form="modalEditarDivida" name="data_inicio" label="Data de início" mascara="data"
      :value="$divida->data_inicio->toDateString()" />
  </x-modal-form>

  @foreach ($historico as $registro)
    <x-modal-form id="modalEditarTaxa{{ $registro->id }}" title="Editar taxa de {{ $registro->mes_referencia->format('m/Y') }}"
      :action="route('dividas.juros.update', [$divida, $registro])" method="PUT">
      <x-campo form="modalEditarTaxa{{ $registro->id }}" name="taxa_juros" label="Taxa de juros do mês (%)" mascara="percentual"
        sufixo="%" :value="number_format($registro->taxa_juros * 100, 2, ',', '')" :ajuda="$avisoCascata" />
    </x-modal-form>
  @endforeach

  @foreach ($parcelas as $parcela)
    <x-modal-form id="modalEditarParcela{{ $parcela->id }}" title="Editar parcela {{ $parcela->numero_parcela }}"
      :action="route('dividas.parcelas.update', [$divida, $parcela])" method="PUT">
      <x-campo form="modalEditarParcela{{ $parcela->id }}" name="numero_parcela" label="Número da parcela" mascara="inteiro"
        :value="$parcela->numero_parcela" />
      <x-campo form="modalEditarParcela{{ $parcela->id }}" name="valor" label="Valor" mascara="moeda"
        :value="number_format($parcela->valor, 2, ',', '.')" />
      <x-campo form="modalEditarParcela{{ $parcela->id }}" name="data_vencimento" label="Data de vencimento" mascara="data"
        :value="$parcela->data_vencimento->toDateString()"
        :ajuda="$parcela->status_pagamento === 'paga' ? 'Parcela paga: '.$avisoCascata : null" />
    </x-modal-form>
  @endforeach
@endsection

@section('page-script')
  <script>
    window.decomposicaoMensal = @json(array_values($decomposicao));
  </script>
  @vite(['resources/assets/js/relatorio-divida.js'])
@endsection
