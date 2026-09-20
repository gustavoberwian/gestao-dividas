@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Verifique seu e-mail')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <x-auth-card title="Verifique seu e-mail ✉️">
    <p class="mb-6">
      Antes de continuar, verifique seu endereco de e-mail clicando no link que enviamos.
      Se voce nao recebeu o e-mail, podemos enviar outro.
    </p>

    @if (session('status') == 'verification-link-sent')
      <div class="alert alert-success">
        Um novo link de verificacao foi enviado para o e-mail cadastrado no seu perfil.
      </div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
      <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Reenviar e-mail de verificacao</button>
      </form>

      <div>
        <a href="{{ route('profile.show') }}">Editar perfil</a>

        <form method="POST" action="{{ route('logout') }}" class="d-inline">
          @csrf
          <button type="submit" class="btn btn-link p-0 ms-2">Sair</button>
        </form>
      </div>
    </div>
  </x-auth-card>
@endsection
