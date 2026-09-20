@props(['type' => 'text', 'name', 'label', 'value' => null, 'required' => false, 'autofocus' => false])

<div class="mb-6">
  <label for="{{ $name }}" class="form-label">{{ $label }}</label>
  <input
    type="{{ $type }}"
    class="form-control @error($name) is-invalid @enderror"
    id="{{ $name }}"
    name="{{ $name }}"
    value="{{ old($name, $value) }}"
    @if ($required) required @endif
    @if ($autofocus) autofocus @endif
  >
  @error($name)
    <span class="invalid-feedback d-block">{{ $message }}</span>
  @enderror
</div>
