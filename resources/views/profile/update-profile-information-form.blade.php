<div class="card">
  <div class="card-header">
    <h5 class="mb-1">Informações do Perfil</h5>
    <p class="text-body-secondary mb-0">Atualize seu nome e e-mail de acesso.</p>
  </div>
  <div class="card-body">
    <form wire:submit="updateProfileInformation">
      @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
        <div x-data="{photoName: null, photoPreview: null}" class="mb-6">
          <input
            type="file"
            id="photo"
            class="d-none"
            wire:model.live="photo"
            x-ref="photo"
            x-on:change="
                photoName = $refs.photo.files[0].name;
                const reader = new FileReader();
                reader.onload = (e) => {
                    photoPreview = e.target.result;
                };
                reader.readAsDataURL($refs.photo.files[0]);
            "
          >

          <label for="photo" class="form-label d-block">Foto</label>

          <div x-show="! photoPreview" class="mb-2">
            <img
              src="{{ $this->user->profile_photo_url }}"
              alt="{{ $this->user->name }}"
              class="rounded-circle"
              style="width: 5rem; height: 5rem; object-fit: cover;"
            >
          </div>

          <div x-show="photoPreview" style="display: none;" class="mb-2">
            <span
              class="d-block rounded-circle"
              style="width: 5rem; height: 5rem; background-size: cover; background-position: center;"
              x-bind:style="'background-image: url(\'' + photoPreview + '\');'"
            ></span>
          </div>

          <button type="button" class="btn btn-outline-secondary btn-sm me-2" x-on:click.prevent="$refs.photo.click()">
            Selecionar nova foto
          </button>

          @if ($this->user->profile_photo_path)
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="deleteProfilePhoto">
              Remover foto
            </button>
          @endif

          @error('photo')
            <span class="text-danger d-block mt-2">{{ $message }}</span>
          @enderror
        </div>
      @endif

      <div class="mb-6">
        <label for="name" class="form-label">Nome</label>
        <input id="name" type="text" class="form-control @error('state.name') is-invalid @enderror" wire:model="state.name" required autocomplete="name">
        @error('state.name')
          <span class="invalid-feedback d-block">{{ $message }}</span>
        @enderror
      </div>

      <div class="mb-6">
        <label for="email" class="form-label">E-mail</label>
        <input id="email" type="email" class="form-control @error('state.email') is-invalid @enderror" wire:model="state.email" required autocomplete="username">
        @error('state.email')
          <span class="invalid-feedback d-block">{{ $message }}</span>
        @enderror

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
          <p class="text-body-secondary mt-2 mb-0">
            Seu e-mail ainda não foi verificado.
            <button type="button" class="btn btn-link p-0" wire:click.prevent="sendEmailVerification">
              Clique aqui para reenviar o e-mail de verificação.
            </button>
          </p>

          @if ($this->verificationLinkSent)
            <p class="text-success mt-2 mb-0">
              Um novo link de verificação foi enviado para o seu e-mail.
            </p>
          @endif
        @endif
      </div>

      <div class="d-flex align-items-center">
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="photo">
          Salvar
        </button>
        <x-saved-message />
      </div>
    </form>
  </div>
</div>
