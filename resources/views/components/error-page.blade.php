@props(['code', 'title', 'message'])

@php
  $configData = Helper::appClasses();
@endphp

<div class="container-xxl container-p-y">
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2" style="line-height: 6rem; font-size: 6rem;">{{ $code }}</h1>
    <h4 class="mb-2 mx-2">{{ $title }}</h4>
    <p class="mb-6 mx-2">{{ $message }}</p>
    <a href="{{ url('/') }}" class="btn btn-primary mb-10">Voltar ao início</a>
    <div class="mt-4">
      <img src="{{ asset('assets/img/illustrations/page-misc-error.png') }}" alt="erro" width="225" class="img-fluid">
    </div>
  </div>
</div>
<div class="container-fluid misc-bg-wrapper">
  <img
    src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}"
    height="355"
    alt="fundo"
    data-app-light-img="illustrations/bg-shape-image-light.png"
    data-app-dark-img="illustrations/bg-shape-image-dark.png"
  >
</div>
