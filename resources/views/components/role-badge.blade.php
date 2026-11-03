{{--
    The role of a login as a coloured pill with a dot: <x-role-badge :user="$user" />
    Colours per role (Admin red, HR amber, Manager indigo, Employee teal) are the .role-badge styles
    in layouts/app.blade.php, with their own dark-mode versions. The users table (JavaScript) builds the same HTML.
--}}
@props(['user'])
<span {{ $attributes->merge(['class' => 'role-badge role-'.$user->role]) }}>{{ $user->role_label }}</span>
