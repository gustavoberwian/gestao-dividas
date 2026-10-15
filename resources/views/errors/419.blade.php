@php
  $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Sessão Expirada')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
  <x-error-page
    code="419"
    title="Sessão expirada ⏳"
    message="Sua sessão expirou. Volte ao início e tente novamente."
  />
@endsection
