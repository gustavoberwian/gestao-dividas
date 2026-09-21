@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Erro no Servidor')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
  <x-error-page
    code="500"
    title="Ops, algo deu errado 🛠️"
    message="Ocorreu um erro interno. Ja fomos notificados e estamos verificando."
  />
@endsection
