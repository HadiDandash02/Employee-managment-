{{-- Live checklist under a password field: each rule turns green while typing (script in layouts/app.blade.php).
     The same rules are enforced by the server: Password::defaults() in AppServiceProvider. Needs $for = the input id. --}}
<ul class="password-rules" data-password-rules="{{ $for }}" aria-live="polite">
    <li data-rule="length"><i class="bi bi-circle"></i> At least 8 characters</li>
    <li data-rule="upper"><i class="bi bi-circle"></i> An uppercase letter (A–Z)</li>
    <li data-rule="lower"><i class="bi bi-circle"></i> A lowercase letter (a–z)</li>
    <li data-rule="number"><i class="bi bi-circle"></i> A number (0–9)</li>
    <li data-rule="symbol"><i class="bi bi-circle"></i> A special character (! @ # $ % …)</li>
</ul>
