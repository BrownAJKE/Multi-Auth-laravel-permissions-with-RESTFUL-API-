@component('mail::message')
# Email Verification

Thank you for signing up.
Your six-digit code is {{ $code }}


Thanks,<br>
{{ config('app.name') }}
@endcomponent
