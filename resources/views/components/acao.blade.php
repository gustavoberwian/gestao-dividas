{{--
  Botao que envia um formulario (PATCH/DELETE). Com "confirmar", ui.js pede confirmacao
  (sweetalert2) antes de enviar.
--}}
@props(['action', 'method' => 'POST', 'confirmar' => null, 'titulo' => null, 'pequeno' => true])

<form action="{{ $action }}" method="POST" class="d-inline"
  @if ($confirmar) data-confirmar="{{ $confirmar }}" @endif>
  @csrf
  @if (strtoupper($method) !== 'POST')
    @method($method)
  @endif
  <button type="submit" title="{{ $titulo }}" @if ($titulo) aria-label="{{ $titulo }}" @endif
    {{ $attributes->class(['btn', 'btn-sm' => $pequeno]) }}>{{ $slot }}</button>
</form>
