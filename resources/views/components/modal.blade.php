@props(['id', 'maxWidth' => 'lg'])

@php
  $id = $id ?? md5($attributes->wire('model'));
  $maxWidthClass = [
      'sm' => 'modal-sm',
      'md' => '',
      'lg' => 'modal-lg',
      'xl' => 'modal-xl',
      '2xl' => 'modal-xl',
  ][$maxWidth];
@endphp

<div
  x-data="{ show: @entangle($attributes->wire('model')) }"
  x-init="
    $refs.modalEl.addEventListener('hidden.bs.modal', () => { show = false });
    $watch('show', value => {
        const instance = bootstrap.Modal.getOrCreateInstance($refs.modalEl);
        value ? instance.show() : instance.hide();
    });
  "
  wire:ignore.self
>
  <div class="modal fade" id="{{ $id }}" x-ref="modalEl" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered {{ $maxWidthClass }}">
      <div class="modal-content">
        {{ $slot }}
      </div>
    </div>
  </div>
</div>
