{{--
  Modal com formulario. O campo oculto "_form" identifica qual formulario foi enviado:
  em caso de erro de validacao, ui.js reabre este modal e x-campo mostra os erros nele.
--}}
@props(['id', 'title', 'action', 'method' => 'POST', 'submit' => 'Salvar'])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" aria-labelledby="{{ $id }}Titulo">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ $action }}" method="POST" novalidate>
        @csrf
        @if (strtoupper($method) !== 'POST')
          @method($method)
        @endif
        <input type="hidden" name="_form" value="{{ $id }}">
        <div class="modal-header">
          <h5 class="modal-title" id="{{ $id }}Titulo">{{ $title }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          {{ $slot }}
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">{{ $submit }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
