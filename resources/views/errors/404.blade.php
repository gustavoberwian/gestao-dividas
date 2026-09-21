@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Pagina Nao Encontrada')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
  <x-error-page
    code="404"
    title="Pagina nao encontrada ⚠️"
    message="Nao conseguimos encontrar a pagina que voce esta procurando."
  />
@endsection
