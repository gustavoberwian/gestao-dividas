<div class="card">
  <div class="card-header">
    <h5 class="mb-1">Sessões do Navegador</h5>
    <p class="text-body-secondary mb-0">Gerencie e encerre suas sessões ativas em outros navegadores e dispositivos.</p>
  </div>
  <div class="card-body">
    <p class="text-body-secondary">
      Se necessário, você pode encerrar todas as suas outras sessões de navegador em todos os
      dispositivos. Algumas das suas sessões recentes estão listadas abaixo; esta lista pode não ser
      completa. Se você acha que sua conta foi comprometida, também deveria atualizar sua senha.
    </p>

    @if (count($this->sessions) > 0)
      <div class="mb-4">
        @foreach ($this->sessions as $session)
          <div class="d-flex align-items-center mb-3">
            <div>
              @if ($session->agent->isDesktop())
                <i class="icon-base ti tabler-device-desktop icon-lg text-body-secondary"></i>
              @else
                <i class="icon-base ti tabler-device-mobile icon-lg text-body-secondary"></i>
              @endif
            </div>

            <div class="ms-3">
              <div>
                {{ $session->agent->platform() ? $session->agent->platform() : 'Desconhecido' }} - {{ $session->agent->browser() ? $session->agent->browser() : 'Desconhecido' }}
              </div>

              <div class="text-body-secondary small">
                {{ $session->ip_address }},

                @if ($session->is_current_device)
                  <span class="text-success fw-semibold">Este dispositivo</span>
                @else
                  Ativo pela ultima vez {{ $session->last_active }}
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <div class="d-flex align-items-center">
      <button type="button" class="btn btn-primary" wire:click="confirmLogout" wire:loading.attr="disabled">
        Encerrar outras sessões
      </button>

      <x-saved-message on="loggedOut">Feito.</x-saved-message>
    </div>

    <x-dialog-modal wire:model.live="confirmingLogout">
      <x-slot name="title">
        Encerrar outras sessões do navegador
      </x-slot>

      <x-slot name="content">
        Digite sua senha para confirmar que deseja encerrar suas outras sessões de navegador em
        todos os dispositivos.

        <div class="mt-4" x-data="{}" x-on:confirming-logout-other-browser-sessions.window="setTimeout(() => $refs.password.focus(), 250)">
          <input
            type="password"
            class="form-control @error('password') is-invalid @enderror"
            autocomplete="current-password"
            placeholder="Senha"
            x-ref="password"
            wire:model="password"
            wire:keydown.enter="logoutOtherBrowserSessions"
          >
          @error('password')
            <span class="invalid-feedback d-block">{{ $message }}</span>
          @enderror
        </div>
      </x-slot>

      <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" wire:click="$toggle('confirmingLogout')" wire:loading.attr="disabled">
          Cancelar
        </button>

        <button type="button" class="btn btn-primary ms-2" wire:click="logoutOtherBrowserSessions" wire:loading.attr="disabled">
          Encerrar outras sessões
        </button>
      </x-slot>
    </x-dialog-modal>
  </div>
</div>
