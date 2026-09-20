@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Verificacao em duas etapas')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <x-auth-card title="Verificacao em duas etapas 🛡️">
    @include('partials.alert-status')

    <p id="twoFactorCodeHint" class="mb-6">
      Confirme o acesso a sua conta digitando o codigo gerado pelo seu aplicativo autenticador.
    </p>
    <p id="twoFactorRecoveryHint" class="mb-6 d-none">
      Confirme o acesso a sua conta digitando um dos seus codigos de recuperacao.
    </p>

    <form method="POST" action="{{ route('two-factor.login') }}">
      @csrf

      <div id="twoFactorCodeField">
        <x-auth-input type="text" name="code" label="Codigo" autofocus />
      </div>
      <div id="twoFactorRecoveryField" class="d-none">
        <x-auth-input type="text" name="recovery_code" label="Codigo de recuperacao" />
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <button type="button" id="twoFactorToggle" class="btn btn-text-secondary">
          Usar um codigo de recuperacao
        </button>
        <button class="btn btn-primary" type="submit">Entrar</button>
      </div>
    </form>
  </x-auth-card>
@endsection

@section('page-script')
  <script>
    document.getElementById('twoFactorToggle').addEventListener('click', function () {
      const usandoRecovery = !document.getElementById('twoFactorRecoveryField').classList.contains('d-none');

      document.getElementById('twoFactorCodeField').classList.toggle('d-none', !usandoRecovery);
      document.getElementById('twoFactorCodeHint').classList.toggle('d-none', !usandoRecovery);
      document.getElementById('twoFactorRecoveryField').classList.toggle('d-none', usandoRecovery);
      document.getElementById('twoFactorRecoveryHint').classList.toggle('d-none', usandoRecovery);
      this.textContent = usandoRecovery ? 'Usar um codigo de recuperacao' : 'Usar um codigo de autenticacao';
    });
  </script>
@endsection
