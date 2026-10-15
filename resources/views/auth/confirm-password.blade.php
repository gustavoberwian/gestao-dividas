@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Confirmar senha')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <x-auth-card title="Área segura 🔐" subtitle="Confirme sua senha antes de continuar">

    <form method="POST" action="{{ route('password.confirm') }}">
      @csrf

      <x-auth-password-input name="password" label="Senha" autofocus />

      <div class="mb-6">
        <button class="btn btn-primary d-grid w-100" type="submit">Confirmar</button>
      </div>
    </form>
  </x-auth-card>
@endsection
