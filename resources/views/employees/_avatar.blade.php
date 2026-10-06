@php
    $size = $size ?? 40;
@endphp
<span class="avatar text-bg-{{ $employee->avatar_color }}"
      style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.4) }}px"
      aria-hidden="true">
    @if ($employee->initials !== '')
        {{ $employee->initials }}
    @else
        <i class="bi bi-person"></i>
    @endif
</span>
