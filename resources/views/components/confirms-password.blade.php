@props(['title' => 'Confirmar Senha', 'content' => 'Por segurança, confirme sua senha para continuar.', 'button' => 'Confirmar'])

@php
    $confirmableId = md5($attributes->wire('then'));
@endphp

<span
    {{ $attributes->wire('then') }}
    x-data
    x-ref="span"
    x-on:click="$wire.startConfirmingPassword('{{ $confirmableId }}')"
    x-on:password-confirmed.window="setTimeout(() => $event.detail.id === '{{ $confirmableId }}' && $refs.span.dispatchEvent(new CustomEvent('then', { bubbles: false })), 250);"
>
    {{ $slot }}
</span>

@once
<x-dialog-modal wire:model.live="confirmingPassword">
    <x-slot name="title">
        {{ $title }}
    </x-slot>

    <x-slot name="content">
        {{ $content }}

        <div class="mt-4" x-data="{}" x-on:confirming-password.window="setTimeout(() => $refs.confirmable_password.focus(), 250)">
            <input
                type="password"
                class="form-control"
                placeholder="Senha"
                autocomplete="current-password"
                x-ref="confirmable_password"
                wire:model="confirmablePassword"
                wire:keydown.enter="confirmPassword"
            >
            @error('confirmable_password')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </x-slot>

    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" wire:click="stopConfirmingPassword" wire:loading.attr="disabled">
            Cancelar
        </button>

        <button type="button" class="btn btn-primary ms-2" wire:click="confirmPassword" wire:loading.attr="disabled">
            {{ $button }}
        </button>
    </x-slot>
</x-dialog-modal>
@endonce
