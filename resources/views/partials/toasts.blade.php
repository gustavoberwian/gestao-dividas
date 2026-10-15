{{--
  Avisos da requisicao anterior, exibidos como toasts por resources/assets/js/ui.js.
  Incluido uma unica vez no layout (layouts/sections/scripts).
--}}
@php
  $avisos = [];
  $status = session('status');
  // Fortify/Jetstream usam codigos ("verification-link-sent") que as proprias telas tratam
  if (is_string($status) && ! preg_match('/^[a-z0-9-]+$/', $status)) {
      $avisos[] = ['tipo' => 'success', 'mensagem' => $status];
  }
  if (is_string(session('error'))) {
      $avisos[] = ['tipo' => 'error', 'mensagem' => session('error')];
  }
  $avisosDaPagina = ['avisos' => $avisos, 'erros' => $errors->getMessages(), 'formulario' => old('_form')];
@endphp
<script>
  window.avisosDaPagina = @json($avisosDaPagina);
</script>
