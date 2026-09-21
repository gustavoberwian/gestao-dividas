@extends('layouts/layoutMaster')

@section('title', 'Meu Perfil')

@section('content')
  @include('partials.alert-status')

  <h4 class="mb-4">Meu Perfil</h4>

  @if (Laravel\Fortify\Features::canUpdateProfileInformation())
    <div class="mb-4">
      @livewire('profile.update-profile-information-form')
    </div>
  @endif

  @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
    <div class="mb-4">
      @livewire('profile.update-password-form')
    </div>
  @endif

  @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
    <div class="mb-4">
      @livewire('profile.two-factor-authentication-form')
    </div>
  @endif

  <div class="mb-4">
    @livewire('profile.logout-other-browser-sessions-form')
  </div>

  @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
    <div class="mb-4">
      @livewire('profile.delete-user-form')
    </div>
  @endif
@endsection
