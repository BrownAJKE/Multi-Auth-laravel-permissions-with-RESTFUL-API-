@component('mail::message')
# Reset Password Notification

Dear {{ $name }}, 

Your password has been reset successfully.

<p>If youb did not make this changes, or if you believe an unauthorised person has accessed your account, contact our support immidiately </p>
  
@component('mail::button', ['url' => ''])
    Contact Support
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
