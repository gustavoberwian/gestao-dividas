@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Criar conta')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <x-auth-card title="Comece agora 🚀" subtitle="Crie sua conta para organizar suas dívidas e cartões">

    <form method="POST" action="{{ route('register') }}">
      @csrf

      <x-auth-input type="text" name="name" label="Nome" :value="old('name')" required autofocus />
      <x-auth-input type="email" name="email" label="E-mail" :value="old('email')" required />
      <x-auth-password-input name="password" label="Senha" />
      <x-auth-password-input name="password_confirmation" label="Confirme a senha" />

      @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
        <div class="mb-6 form-check">
          <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
          <label class="form-check-label" for="terms">
            {!! __('Eu concordo com os :terms_of_service e a :privacy_policy', [
                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'">Termos de Serviço</a>',
                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'">Política de Privacidade</a>',
            ]) !!}
          </label>
          @error('terms')
            <span class="invalid-feedback d-block">{{ $message }}</span>
          @enderror
        </div>
      @endif

      <div class="mb-6">
        <button class="btn btn-primary d-grid w-100" type="submit">Criar conta</button>
      </div>
    </form>

    <p class="text-center">
      <span>Já tem uma conta?</span>
      <a href="{{ route('login') }}">
        <span>Entrar</span>
      </a>
    </p>
  </x-auth-card>
@endsection
