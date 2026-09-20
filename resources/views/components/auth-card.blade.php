@props(['title', 'subtitle' => null])

<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>

          <h4 class="mb-1">{{ $title }}</h4>
          @if ($subtitle)
            <p class="mb-6">{{ $subtitle }}</p>
          @endif

          {{ $slot }}
        </div>
      </div>
    </div>
  </div>
</div>
