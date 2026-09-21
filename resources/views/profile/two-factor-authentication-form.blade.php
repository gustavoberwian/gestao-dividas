<div class="card">
  <div class="card-header">
    <h5 class="mb-1">Autenticacao em Duas Etapas</h5>
    <p class="text-body-secondary mb-0">Adicione uma camada extra de seguranca a sua conta.</p>
  </div>
  <div class="card-body">
    <h6 class="mb-2">
      @if ($this->enabled)
        @if ($showingConfirmation)
          Finalize a ativacao da autenticacao em duas etapas.
        @else
          A autenticacao em duas etapas esta ativada.
        @endif
      @else
        A autenticacao em duas etapas nao esta ativada.
      @endif
    </h6>

    <p class="text-body-secondary">
      Quando a autenticacao em duas etapas estiver ativada, sera solicitado um codigo seguro durante
      a autenticacao. Voce pode obter esse codigo no aplicativo autenticador do seu telefone.
    </p>

    @if ($this->enabled)
      @if ($showingQrCode)
        <p class="fw-semibold">
          @if ($showingConfirmation)
            Para finalizar a ativacao, escaneie o QR code abaixo com o aplicativo autenticador do seu
            telefone ou informe a chave manualmente e digite o codigo gerado.
          @else
            A autenticacao em duas etapas esta ativa. Escaneie o QR code abaixo com o aplicativo
            autenticador do seu telefone ou informe a chave manualmente.
          @endif
        </p>

        <div class="d-inline-block p-2 bg-white mb-3">
          {!! $this->user->twoFactorQrCodeSvg() !!}
        </div>

        <p class="fw-semibold">
          Chave: {{ decrypt($this->user->two_factor_secret) }}
        </p>

        @if ($showingConfirmation)
          <div class="mb-6" style="max-width: 20rem;">
            <label for="code" class="form-label">Codigo</label>
            <input
              id="code"
              type="text"
              name="code"
              class="form-control @error('code') is-invalid @enderror"
              inputmode="numeric"
              autofocus
              autocomplete="one-time-code"
              wire:model="code"
              wire:keydown.enter="confirmTwoFactorAuthentication"
            >
            @error('code')
              <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
          </div>
        @endif
      @endif

      @if ($showingRecoveryCodes)
        <p class="fw-semibold">
          Guarde estes codigos de recuperacao em um gerenciador de senhas seguro. Eles podem ser
          usados para recuperar o acesso a sua conta caso voce perca o dispositivo autenticador.
        </p>

        <div class="bg-light rounded p-3 font-monospace" style="max-width: 20rem;">
          @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $recoveryCode)
            <div>{{ $recoveryCode }}</div>
          @endforeach
        </div>
      @endif
    @endif

    <div class="mt-4">
      @if (! $this->enabled)
        <x-confirms-password wire:then="enableTwoFactorAuthentication">
          <button type="button" class="btn btn-primary" wire:loading.attr="disabled">Ativar</button>
        </x-confirms-password>
      @else
        @if ($showingRecoveryCodes)
          <x-confirms-password wire:then="regenerateRecoveryCodes">
            <button type="button" class="btn btn-outline-secondary me-2">Gerar novos codigos de recuperacao</button>
          </x-confirms-password>
        @elseif ($showingConfirmation)
          <x-confirms-password wire:then="confirmTwoFactorAuthentication">
            <button type="button" class="btn btn-primary me-2" wire:loading.attr="disabled">Confirmar</button>
          </x-confirms-password>
        @else
          <x-confirms-password wire:then="showRecoveryCodes">
            <button type="button" class="btn btn-outline-secondary me-2">Ver codigos de recuperacao</button>
          </x-confirms-password>
        @endif

        @if ($showingConfirmation)
          <x-confirms-password wire:then="disableTwoFactorAuthentication">
            <button type="button" class="btn btn-outline-secondary" wire:loading.attr="disabled">Cancelar</button>
          </x-confirms-password>
        @else
          <x-confirms-password wire:then="disableTwoFactorAuthentication">
            <button type="button" class="btn btn-outline-danger" wire:loading.attr="disabled">Desativar</button>
          </x-confirms-password>
        @endif
      @endif
    </div>
  </div>
</div>
