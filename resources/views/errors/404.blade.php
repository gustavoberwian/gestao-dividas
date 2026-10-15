@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Página Não Encontrada')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
  <x-error-page
    code="404"
    title="Página não encontrada ⚠️"
    message="Não conseguimos encontrar a página que você está procurando."
  />
@endsection
