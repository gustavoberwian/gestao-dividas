{{-- Badge da situacao derivada de parcela/fatura (Parcela::situacao(), Fatura::situacao()) --}}
@props(['situacao'])

@php
  [$rotulo, $classe] = match ($situacao) {
      'paga' => ['Paga', 'bg-label-success'],
      'vencida' => ['Vencida', 'bg-label-danger'],
      'vence_hoje' => ['Vence hoje', 'bg-label-warning'],
      'fechada' => ['Fechada', 'bg-label-amarelo'],
      'aberta' => ['Aberta', 'bg-label-info'],
      default => ['Pendente', 'bg-label-secondary'],
  };
@endphp

<span {{ $attributes->class(['badge', $classe]) }}>{{ $rotulo }}</span>
