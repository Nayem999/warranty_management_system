<x-mail::message>
# Welcome to {{ config('app.name') }}!

Dear {{ $user->first_name }} {{ $user->last_name }},

Thank you for being part of {{ config('app.name') }}. Your account has been created successfully.

**Login Details:**
- **Email:** {{ $user->email }}
- **Password:** {{ $password }}

**Login URL:**
<x-mail::button :url="config('app.frontend_url', 'http://localhost:3000') . '/login'">
Login to Your Account
</x-mail::button>

Please change your password after first login for security purposes.

If you have any questions, feel free to contact our support team.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>