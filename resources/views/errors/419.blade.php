@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Sessao Expirada')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
  <x-error-page
    code="419"
    title="Sessao expirada ⏳"
    message="Sua sessao expirou. Volte ao inicio e tente novamente."
  />
@endsection
