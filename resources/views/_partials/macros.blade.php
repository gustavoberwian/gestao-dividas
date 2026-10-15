@php
  $width = $width ?? '32';
  $height = $height ?? '32';
@endphp

{{-- Marca da aplicacao: iniciais "GD" (Gestao de Dividas) --}}
<span class="text-primary">
  <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <rect width="32" height="32" rx="7" fill="currentColor" />
    <text x="16" y="21.5" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="14"
      font-weight="700" fill="#fff">GD</text>
  </svg>
</span>
