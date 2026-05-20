@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Surya Parfum" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="{{ asset('logo.jpeg') }}" class="size-6 object-contain" alt="Logo Surya Parfum">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Surya Parfum" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden">
            <img src="{{ asset('logo.jpeg') }}" class="size-6 object-contain" alt="Logo Surya Parfum">
        </x-slot>
    </flux:brand>
@endif