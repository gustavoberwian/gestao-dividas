{{--
  Campo de formulario das telas do sistema, com erro de validacao inline.

  - mascara: moeda | percentual | data | mes | inteiro (aplicada por resources/assets/js/ui.js)
  - form: identificador do formulario (ver x-modal-form). Erros e valores antigos (old) so
    aparecem no formulario que foi enviado, para modais com campos de mesmo nome nao
    mostrarem o erro um do outro.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'mascara' => null,
    'required' => true,
    'prefixo' => null,
    'sufixo' => null,
    'ajuda' => null,
    'form' => null,
])

@php
  $id = ($form ? $form.'_' : '').$name;
  $ativo = $form === null || old('_form') === $form;
  $erro = $ativo ? $errors->first($name) : null;
  $valor = $ativo ? old($name, $value) : $value;
  $comGrupo = $prefixo || $sufixo;
@endphp

<div class="mb-4">
  <label class="form-label" for="{{ $id }}">{{ $label }}</label>
  @if ($comGrupo)
    <div @class(['input-group', 'has-validation', 'is-invalid' => $erro])>
  @endif
  @if ($prefixo)
    <span class="input-group-text">{{ $prefixo }}</span>
  @endif
  <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $valor }}"
    @if ($mascara) data-mascara="{{ $mascara }}" @endif
    @if (in_array($mascara, ['moeda', 'percentual'])) inputmode="decimal" autocomplete="off" @endif
    @if ($mascara === 'inteiro') inputmode="numeric" autocomplete="off" @endif
    @required($required)
    {{ $attributes->class(['form-control', 'is-invalid' => $erro]) }}>
  @if ($sufixo)
    <span class="input-group-text">{{ $sufixo }}</span>
  @endif
  @if ($erro)
    <div class="invalid-feedback">{{ $erro }}</div>
  @endif
  @if ($comGrupo)
    </div>
  @endif
  @if ($ajuda)
    <div class="form-text">{{ $ajuda }}</div>
  @endif
</div>
