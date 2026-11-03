{{--
    The standard button of the system. With href it is a link (<a>), otherwise a <button>.

    <x-button type="submit">Save</x-button>
    <x-button variant="secondary" :href="route('employees.index')">Cancel</x-button>
    <x-button variant="danger-outline" size="sm" icon="bi-trash" type="submit">Delete</x-button>

    variant: primary (teal, the main action), secondary (white: Cancel, Clear, Back…), success (green),
             info (blue), warning (yellow), danger (red), danger-outline (red border)
    size:    sm, or leave it out for the normal size
    icon:    a Bootstrap Icons class. Without text in the button, it becomes an icon-only button.
    Everything else (id, class, data-*, onclick, aria-label…) is passed on to the HTML tag.
    The colours and sizes are in the "Buttons" part of layouts/app.blade.php.
--}}
@props(['variant' => 'primary', 'size' => null, 'icon' => null, 'href' => null, 'type' => 'button'])
@php($variants = ['primary' => 'btn-primary', 'secondary' => 'btn-soft', 'success' => 'btn-success', 'info' => 'btn-info', 'warning' => 'btn-warning', 'danger' => 'btn-danger', 'danger-outline' => 'btn-outline-danger'])
@php($classes = 'btn '.$variants[$variant].($size ? " btn-{$size}" : ''))
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<i class="bi {{ $icon }} {{ $slot->isEmpty() ? '' : 'me-1' }}"></i>@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>@if ($icon)<i class="bi {{ $icon }} {{ $slot->isEmpty() ? '' : 'me-1' }}"></i>@endif{{ $slot }}</button>
@endif
