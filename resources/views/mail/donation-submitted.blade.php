<x-mail::message>
# {{ __('New donation submitted') }}

**{{ __('Donor') }}:** {{ $donorName }}

**{{ __('Email') }}:** {{ $donorEmail }}

@if ($donorPhone)
**{{ __('Phone') }}:** {{ $donorPhone }}
@endif

**{{ __('Amount') }}:** {{ $amount }}

**{{ __('Purpose') }}:** {{ $purpose }}

@if ($appeal)
**{{ __('Gift') }}:** {{ $appeal }}
@endif

**{{ __('Payment method') }}:** {{ $method }}

**{{ __('Status') }}:** {{ $status }}

@if ($status === 'pending')
{{ __('This gift is pending until it is confirmed.') }}
@endif

<x-mail::button :url="$adminUrl">
{{ __('View donations') }}
</x-mail::button>
</x-mail::message>
