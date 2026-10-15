'use strict';

/**
 * Dashboard: grafico de evolucao do saldo devedor, uma linha por divida.
 * Cada serie comeca no valor principal (data de inicio) e tem um ponto no fim de cada
 * mes calculado; o tooltip mostra a taxa e o total pago no mes.
 */
(function () {
  const data = window.dashboardData;
  const elemento = document.querySelector('#saldoDevedorChart');

  if (!data || !elemento || typeof ApexCharts === 'undefined') {
    return;
  }

  const moeda = valor => 'R$ ' + Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const percentual = valor => Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '%';
  const dataBr = iso => iso.split('-').reverse().join('/');
  const cores = window.config ? window.config.colors : {};

  // "2026-07-01" -> meio-dia local, para a data nao "voltar" um dia por causa do UTC
  const timestampLocal = iso => {
    const [ano, mes, dia] = iso.split('-').map(Number);
    return new Date(ano, mes - 1, dia, 12).getTime();
  };
  const todasAsSeries = data.seriesSaldoDevedor.map(serie => ({
    ...serie,
    data: serie.data.map(ponto => ({ ...ponto, data: ponto.x, x: timestampLocal(ponto.x) }))
  }));

  const grafico = new ApexCharts(elemento, {
    chart: {
      type: 'line',
      height: 320,
      toolbar: { show: false },
      zoom: { enabled: false },
      fontFamily: window.config ? window.config.fontFamily : undefined,
      foreColor: cores.textMuted,
      defaultLocale: 'pt-br',
      locales: [
        {
          name: 'pt-br',
          options: {
            months: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            shortMonths: ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'],
            days: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
            shortDays: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']
          }
        }
      ]
    },
    series: todasAsSeries,
    colors: [cores.danger, cores.primary, cores.warning, cores.info, cores.secondary, cores.success],
    stroke: { curve: 'straight', width: 3 },
    markers: { size: 5, strokeWidth: 0, hover: { size: 7 } },
    dataLabels: { enabled: false },
    grid: { borderColor: cores.borderColor },
    legend: { position: 'bottom', showForSingleSeries: false },
    noData: { text: 'Lance a taxa de juros de um mês para ver a evolução.' },
    xaxis: {
      type: 'datetime',
      labels: {
        datetimeUTC: false,
        datetimeFormatter: { year: 'yyyy', month: 'MMM/yyyy', day: 'dd/MM', hour: 'dd/MM' }
      },
      tooltip: { enabled: false }
    },
    yaxis: {
      labels: { formatter: moeda }
    },
    tooltip: {
      shared: false,
      intersect: true,
      custom: ({ seriesIndex, dataPointIndex, w }) => {
        const serie = w.config.series[seriesIndex];
        const ponto = serie.data[dataPointIndex];
        const linhas = ponto.inicial
          ? [`Início em ${dataBr(ponto.data)}`, `Valor emprestado: <strong>${moeda(ponto.y)}</strong>`]
          : [
              `Fim de ${ponto.mes}`,
              `Saldo devedor: <strong>${moeda(ponto.y)}</strong>`,
              `Taxa do mês: ${percentual(ponto.taxa)}`,
              `Pago no mês: ${moeda(ponto.pago)}`
            ];
        return `<div class="px-3 py-2"><div class="fw-medium mb-1">${serie.name}</div>${linhas.join('<br>')}</div>`;
      }
    }
  });

  grafico.render();

  const filtro = document.querySelector('#filtroDivida');
  if (filtro) {
    filtro.addEventListener('change', () => {
      const id = filtro.value;
      grafico.updateSeries(id ? todasAsSeries.filter(serie => String(serie.id) === id) : todasAsSeries);
    });
  }
})();
