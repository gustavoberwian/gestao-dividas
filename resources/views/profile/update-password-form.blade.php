<div class="card">
  <div class="card-header">
    <h5 class="mb-1">Atualizar Senha</h5>
    <p class="text-body-secondary mb-0">Use uma senha longa e aleatoria para manter sua conta segura.</p>
  </div>
  <div class="card-body">
    <form wire:submit="updatePassword">
      <div class="mb-6">
        <label for="current_password" class="form-label">Senha atual</label>
        <input id="current_password" type="password" class="form-control @error('state.current_password') is-invalid @enderror" wire:model="state.current_password" autocomplete="current-password">
        @error('state.current_password')
          <span class="invalid-feedback d-block">{{ $message }}</span>
        @enderror
      </div>

      <div class="mb-6">
        <label for="password" class="form-label">Nova senha</label>
        <input id="password" type="password" class="form-control @error('state.password') is-invalid @enderror" wire:model="state.password" autocomplete="new-password">
        @error('state.password')
          <span class="invalid-feedback d-block">{{ $message }}</span>
        @enderror
      </div>

      <div class="mb-6">
        <label for="password_confirmation" class="form-label">Confirme a nova senha</label>
        <input id="password_confirmation" type="password" class="form-control @error('state.password_confirmation') is-invalid @enderror" wire:model="state.password_confirmation" autocomplete="new-password">
        @error('state.password_confirmation')
          <span class="invalid-feedback d-block">{{ $message }}</span>
        @enderror
      </div>

      <div class="d-flex align-items-center">
        <button type="submit" class="btn btn-primary">Salvar</button>
        <x-saved-message />
      </div>
    </form>
  </div>
</div>
