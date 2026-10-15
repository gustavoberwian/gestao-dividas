'use strict';

/**
 * Relatorio da divida: colunas empilhadas por mes com os juros cobrados e a
 * amortizacao (pagamento - juros). Amortizacao negativa (pagamento menor que os
 * juros) aparece como zero no grafico; o valor exato esta na tabela.
 */
(function () {
  const linhas = window.decomposicaoMensal;
  const elemento = document.querySelector('#jurosAmortizacaoChart');

  if (!linhas || !linhas.length || !elemento || typeof ApexCharts === 'undefined') {
    return;
  }

  const moeda = valor => 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const cores = window.config ? window.config.colors : {};

  new ApexCharts(elemento, {
    chart: {
      type: 'bar',
      stacked: true,
      height: 300,
      toolbar: { show: false },
      fontFamily: window.config ? window.config.fontFamily : undefined,
      foreColor: cores.textMuted
    },
    series: [
      { name: 'Juros do mês', data: linhas.map(l => l.juros) },
      { name: 'Amortização', data: linhas.map(l => Math.max(l.amortizacao, 0)) }
    ],
    colors: [cores.danger, cores.success],
    plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
    dataLabels: { enabled: false },
    grid: { borderColor: cores.borderColor },
    legend: { position: 'bottom' },
    xaxis: { categories: linhas.map(l => l.mes) },
    yaxis: { labels: { formatter: moeda } },
    tooltip: {
      shared: true,
      intersect: false,
      y: { formatter: moeda }
    }
  }).render();
})();
