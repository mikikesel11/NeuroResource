<x-mail::message>
# One Quick Step

Thanks for your interest in **{{ $resourceTitle }}**.

To confirm your Email and unlock your Resource, please select the button below.

<x-mail::button :url="$confirmUrl">
Confirm & Unlock
</x-mail::button>

If you didn't request this, you can safely ignore this Email — nothing will be
sent to you.

Take care,<br>
The {{ config('app.name') }} Team
</x-mail::message>
