@props(['on' => 'saved'])

<span
  class="text-success ms-3"
  x-data="{ shown: false, timeout: null }"
  x-init="@this.on('{{ $on }}', () => { shown = true; clearTimeout(timeout); timeout = setTimeout(() => { shown = false }, 2000); })"
  x-show="shown"
  style="display: none;"
>
  {{ $slot->isEmpty() ? 'Salvo.' : $slot }}
</span>
