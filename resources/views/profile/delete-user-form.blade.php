<div class="card border-danger">
  <div class="card-header">
    <h5 class="mb-1">Excluir Conta</h5>
    <p class="text-body-secondary mb-0">Exclua permanentemente sua conta.</p>
  </div>
  <div class="card-body">
    <p class="text-body-secondary">
      Depois que sua conta for excluida, todos os seus recursos e dados serao removidos
      permanentemente. Antes de excluir sua conta, baixe qualquer dado ou informacao que deseje manter.
    </p>

    <button type="button" class="btn btn-danger" wire:click="confirmUserDeletion" wire:loading.attr="disabled">
      Excluir Conta
    </button>

    <x-dialog-modal wire:model.live="confirmingUserDeletion">
      <x-slot name="title">
        Excluir Conta
      </x-slot>

      <x-slot name="content">
        Tem certeza que deseja excluir sua conta? Depois que sua conta for excluida, todos os seus
        recursos e dados serao removidos permanentemente. Digite sua senha para confirmar.

        <div class="mt-4" x-data="{}" x-on:confirming-delete-user.window="setTimeout(() => $refs.password.focus(), 250)">
          <input
            type="password"
            class="form-control @error('password') is-invalid @enderror"
            autocomplete="current-password"
            placeholder="Senha"
            x-ref="password"
            wire:model="password"
            wire:keydown.enter="deleteUser"
          >
          @error('password')
            <span class="invalid-feedback d-block">{{ $message }}</span>
          @enderror
        </div>
      </x-slot>

      <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" wire:click="$toggle('confirmingUserDeletion')" wire:loading.attr="disabled">
          Cancelar
        </button>

        <button type="button" class="btn btn-danger ms-2" wire:click="deleteUser" wire:loading.attr="disabled">
          Excluir Conta
        </button>
      </x-slot>
    </x-dialog-modal>
  </div>
</div>
