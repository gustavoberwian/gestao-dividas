'use strict';

(function () {
  const data = window.dashboardData;

  if (!data || typeof ApexCharts === 'undefined') {
    return;
  }

  const evolucaoSaldoDevedorEl = document.querySelector('#evolucaoSaldoDevedorChart');

  if (evolucaoSaldoDevedorEl) {
    if (data.evolucaoSaldoDevedorValores.length === 0) {
      evolucaoSaldoDevedorEl.innerHTML =
        '<p class="text-body-secondary text-center mb-0 py-5">Lance uma taxa de juros em alguma divida para ver a evolucao do saldo devedor aqui.</p>';
    } else {
      new ApexCharts(evolucaoSaldoDevedorEl, {
        chart: {
          type: 'line',
          height: 300,
          toolbar: { show: false },
        },
        series: [
          {
            name: 'Saldo devedor total',
            data: data.evolucaoSaldoDevedorValores,
          },
        ],
        xaxis: {
          categories: data.evolucaoSaldoDevedorLabels,
        },
        yaxis: {
          labels: {
            formatter: (valor) => 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 }),
          },
        },
        tooltip: {
          y: {
            formatter: (valor) => 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 }),
          },
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
      }).render();
    }
  }

  const composicaoOrcamentoEl = document.querySelector('#composicaoOrcamentoChart');

  if (composicaoOrcamentoEl) {
    new ApexCharts(composicaoOrcamentoEl, {
      chart: {
        type: 'donut',
        height: 300,
      },
      series: [
        data.totalFaturasAbertas,
        data.totalParcelasDividasDoMes,
        Math.max(data.saldoDisponivel, 0),
      ],
      labels: ['Faturas em aberto', 'Parcelas de dividas do mes', 'Saldo disponivel'],
      legend: { position: 'bottom' },
      tooltip: {
        y: {
          formatter: (valor) => 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 }),
        },
      },
    }).render();
  }
})();
