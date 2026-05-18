<x-mail::message>
# Welcome to {{ config('app.name') }}!

Dear {{ $customer->customer_name }},

Thank you for registering with {{ config('app.name') }}. Your customer account has been created successfully.

**Your Account Details:**
- **Customer Name:** {{ $customer->customer_name }}
- **Contact Person:** {{ $customer->contact_person }}
- **Email:** {{ $customer->email }}
- **Phone:** {{ $customer->phone }}
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
