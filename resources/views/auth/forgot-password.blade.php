@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Esqueci minha senha')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <x-auth-card
    title="Esqueceu a senha? 🔒"
    subtitle="Informe seu e-mail e enviaremos um link para você cadastrar uma nova senha"
  >

    <form method="POST" action="{{ route('password.email') }}">
      @csrf

      <x-auth-input type="email" name="email" label="E-mail" :value="old('email')" required autofocus />

      <div class="mb-6">
        <button class="btn btn-primary d-grid w-100" type="submit">Enviar link de redefinição</button>
      </div>
    </form>

    <p class="text-center">
      <a href="{{ route('login') }}">
        <span>Voltar para o login</span>
      </a>
    </p>
  </x-auth-card>
@endsection
