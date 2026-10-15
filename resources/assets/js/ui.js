/**
 * Comportamentos de interface compartilhados por todas as telas:
 * - toasts (notyf) para avisos e erros da requisicao anterior;
 * - mascaras (cleave-zen) e seletores de data (flatpickr) nos campos com data-mascara;
 * - confirmacao (sweetalert2) antes de enviar formularios com data-confirmar.
 */
import { Notyf } from 'notyf';
import { formatNumeral, formatDate, registerCursorTracker } from 'cleave-zen';
import flatpickr from 'flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect/index.js';
import 'flatpickr/dist/plugins/monthSelect/style.css';
import SwalBase from 'sweetalert2/dist/sweetalert2';

// ---------------------------------------------------------------------------
// Toasts
// ---------------------------------------------------------------------------
const DURACAO = { success: 4000, error: 7000 };

const notyf = new Notyf({
  position: { x: 'right', y: 'top' },
  ripple: false,
  dismissible: true,
  duration: 0 // o tempo e controlado abaixo, para pausar com o mouse em cima
});

export function toast(tipo, mensagem) {
  const notificacao = notyf.open({ type: tipo, message: mensagem });
  const registro = notyf.view.notifications.find(n => n.notification === notificacao);
  const elemento = registro ? registro.node : null;

  let restante = DURACAO[tipo] ?? DURACAO.success;
  let inicio;
  let timer;
  const iniciar = () => {
    inicio = Date.now();
    timer = setTimeout(() => notyf.dismiss(notificacao), restante);
  };
  const pausar = () => {
    clearTimeout(timer);
    restante -= Date.now() - inicio;
  };

  if (elemento) {
    elemento.addEventListener('mouseenter', pausar);
    elemento.addEventListener('mouseleave', iniciar);
  }
  iniciar();

  return notificacao;
}

window.toast = toast;

function exibirAvisosDaPagina() {
  const dados = window.avisosDaPagina || { avisos: [], erros: {}, formulario: null };

  dados.avisos.forEach(aviso => toast(aviso.tipo, aviso.mensagem));

  const campos = Object.keys(dados.erros || {});
  if (!campos.length) return;

  // Reabre o modal do formulario enviado, onde os erros estao destacados
  const form = dados.formulario
    ? document.querySelector(`input[name="_form"][value="${CSS.escape(dados.formulario)}"]`)?.form
    : null;
  const modal = (form || document.querySelector('.is-invalid'))?.closest('.modal');
  if (modal && window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(modal).show();

  // Erros sem campo visivel na tela viram toast com a propria mensagem
  const escopo = form || document;
  let destacados = 0;
  campos.forEach(campo => {
    const input = escopo.querySelector(`[name="${CSS.escape(campo)}"]:not([type="hidden"])`);
    if (input) destacados++;
    else dados.erros[campo].forEach(mensagem => toast('error', mensagem));
  });
  if (destacados) toast('error', 'Verifique os campos destacados.');
}

document.addEventListener('DOMContentLoaded', exibirAvisosDaPagina);

// ---------------------------------------------------------------------------
// Mascaras e seletores de data
// O servidor interpreta o texto mascarado (App\Support\NumeroBr), entao o valor
// e enviado como exibido ("R$ 1.234,56", "1,5"). Datas sao enviadas em Y-m-d.
// ---------------------------------------------------------------------------
const OPCOES_NUMERO = {
  moeda: {
    prefix: 'R$ ',
    delimiter: '.',
    numeralDecimalMark: ',',
    numeralDecimalScale: 2,
    numeralIntegerScale: 10,
    numeralPositiveOnly: true
  },
  percentual: {
    delimiter: '',
    numeralDecimalMark: ',',
    numeralDecimalScale: 2,
    numeralIntegerScale: 3,
    numeralPositiveOnly: true
  }
};

function formatarNumero(input, tipo) {
  if (input.value === '') return;
  let valor = formatNumeral(input.value, OPCOES_NUMERO[tipo]);
  if (tipo === 'moeda' && valor.trim() === 'R$') valor = '';
  if (tipo === 'percentual' && parseFloat(valor.replace(',', '.')) > 100) valor = '100,00';
  input.value = valor;
}

function aplicarMascaraNumero(input, tipo) {
  input.addEventListener('input', () => formatarNumero(input, tipo));
  if (tipo === 'moeda') registerCursorTracker({ input, delimiter: '.', prefix: 'R$ ' });
}

function aplicarMascaraInteiro(input) {
  input.addEventListener('input', () => {
    input.value = input.value.replace(/\D/g, '');
  });
}

function aplicarSeletorData(input, tipo) {
  const modal = input.closest('.modal');
  const comum = {
    locale: Portuguese,
    altInput: true,
    allowInput: true,
    disableMobile: true,
    // dentro de modal, o calendario fica junto do campo (static) para nao perder o
    // foco nem ser posicionado fora do modal
    static: Boolean(modal)
  };
  const opcoes =
    tipo === 'mes'
      ? { ...comum, plugins: [new monthSelectPlugin({ shorthand: true, dateFormat: 'Y-m-01', altFormat: 'm/Y' })] }
      : { ...comum, dateFormat: 'Y-m-d', altFormat: 'd/m/Y' };

  const fp = flatpickr(input, opcoes);

  // mascara ao digitar a data no campo visivel (dd/mm/aaaa ou mm/aaaa)
  if (fp.altInput) {
    const padrao = tipo === 'mes' ? ['m', 'Y'] : ['d', 'm', 'Y'];
    fp.altInput.setAttribute('inputmode', 'numeric');
    fp.altInput.addEventListener('input', () => {
      fp.altInput.value = formatDate(fp.altInput.value, { datePattern: padrao, delimiter: '/' });
    });
  }
}

export function inicializarCampos(raiz = document) {
  raiz.querySelectorAll('[data-mascara]').forEach(input => {
    const tipo = input.dataset.mascara;
    if (!input.dataset.mascaraAtiva) {
      input.dataset.mascaraAtiva = '1';
      if (tipo === 'moeda' || tipo === 'percentual') aplicarMascaraNumero(input, tipo);
      else if (tipo === 'inteiro') aplicarMascaraInteiro(input);
      else if (tipo === 'data' || tipo === 'mes') aplicarSeletorData(input, tipo);
    }
    // (re)formata o valor atual, inclusive ao reabrir um modal ja preenchido
    if (tipo === 'moeda' || tipo === 'percentual') formatarNumero(input, tipo);
    if ((tipo === 'data' || tipo === 'mes') && input._flatpickr) {
      input._flatpickr.setDate(input.value || null, false);
    }
  });
}

window.inicializarCampos = inicializarCampos;

document.addEventListener('DOMContentLoaded', () => {
  inicializarCampos();

  // Links como "/dividas#modalNovaDivida" abrem o modal indicado
  const alvo = window.location.hash ? document.getElementById(window.location.hash.slice(1)) : null;
  if (alvo && alvo.classList.contains('modal') && window.bootstrap) {
    window.bootstrap.Modal.getOrCreateInstance(alvo).show();
  }
});
document.addEventListener('show.bs.modal', evento => inicializarCampos(evento.target));

// ---------------------------------------------------------------------------
// Confirmacao antes de acoes destrutivas (formularios com data-confirmar)
// ---------------------------------------------------------------------------
const Swal = SwalBase.mixin({
  buttonsStyling: false,
  customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-outline-secondary' }
});

document.addEventListener('submit', async evento => {
  const form = evento.target;
  if (!(form instanceof HTMLFormElement) || !form.dataset.confirmar || form.dataset.confirmado) return;

  evento.preventDefault();
  const resposta = await Swal.fire({
    title: 'Tem certeza?',
    text: form.dataset.confirmar,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, continuar',
    cancelButtonText: 'Cancelar',
    focusCancel: true
  });

  if (resposta.isConfirmed) {
    form.dataset.confirmado = '1';
    form.submit();
  }
});
