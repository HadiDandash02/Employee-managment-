{{--
    The roles of a login as coloured pills with a dot, highest first: <x-role-badge :user="$user" />
    A user can have several roles (e.g. Manager + Employee): one pill per role.
    Colours per role (Admin red, HR amber, Manager indigo, Employee teal) are the .role-badge styles
    in layouts/app.blade.php, with their own dark-mode versions. The users table (JavaScript) builds the same HTML.
--}}
@props(['user'])
<span {{ $attributes->merge(['class' => 'd-inline-flex flex-wrap gap-1 align-items-center']) }}>
    @foreach ($user->allRoles() as $i => $role)
        <span class="role-badge role-{{ $role }}">{{ $user->role_labels[$i] }}</span>
    @endforeach
</span>
