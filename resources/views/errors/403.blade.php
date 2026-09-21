@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Acesso Negado')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
  <x-error-page
    code="403"
    title="Acesso negado 🔒"
    message="Voce nao tem permissao para acessar este recurso."
  />
@endsection
