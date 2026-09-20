@props(['name' => 'password', 'label' => 'Senha', 'required' => true, 'autofocus' => false])

<div class="mb-6 form-password-toggle">
  <label class="form-label" for="{{ $name }}">{{ $label }}</label>
  <div class="input-group input-group-merge">
    <input
      type="password"
      id="{{ $name }}"
      class="form-control @error($name) is-invalid @enderror"
      name="{{ $name }}"
      @if ($required) required @endif
      @if ($autofocus) autofocus @endif
    >
    <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
  </div>
  @error($name)
    <span class="invalid-feedback d-block">{{ $message }}</span>
  @enderror
</div>
