@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Redefinir senha')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <x-auth-card title="Nova senha 🔑" subtitle="Escolha uma nova senha para sua conta">
    @include('partials.alert-status')

    <form method="POST" action="{{ route('password.update') }}">
      @csrf

      <input type="hidden" name="token" value="{{ $request->route('token') }}">

      <x-auth-input type="email" name="email" label="E-mail" :value="old('email', $request->email)" required autofocus />
      <x-auth-password-input name="password" label="Nova senha" />
      <x-auth-password-input name="password_confirmation" label="Confirme a nova senha" />

      <div class="mb-6">
        <button class="btn btn-primary d-grid w-100" type="submit">Redefinir senha</button>
      </div>
    </form>
  </x-auth-card>
@endsection
