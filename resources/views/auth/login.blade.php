@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <x-auth-card title="Bem-vindo(a)! 👋" subtitle="Entre na sua conta para continuar">
    @include('partials.alert-status')

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <x-auth-input type="email" name="email" label="E-mail" :value="old('email')" required autofocus />
      <x-auth-password-input name="password" label="Senha" />

      <div class="my-8">
        <div class="d-flex justify-content-between">
          <div class="form-check mb-0 ms-2">
            <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
            <label class="form-check-label" for="remember_me">Lembrar de mim</label>
          </div>
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}">
              <p class="mb-0">Esqueceu a senha?</p>
            </a>
          @endif
        </div>
      </div>

      <div class="mb-6">
        <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
      </div>
    </form>

    <p class="text-center">
      <span>Ainda nao tem uma conta?</span>
      <a href="{{ route('register') }}">
        <span>Criar conta</span>
      </a>
    </p>
  </x-auth-card>
@endsection
