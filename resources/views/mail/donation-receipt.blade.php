<x-mail::message>
# {{ __('Thank you for your donation') }}

{{ __('Dear :name,', ['name' => $donorName ?? __('Donor')]) }}

{{ __('We received your generous gift of :amount.', ['amount' => $giftAmount]) }}

**{{ __('Designation') }}:** {{ $campaignLabel }}

**{{ __('Date') }}:** {{ $date }}

**{{ __('Reference') }}:** {{ $reference }}

@if ($feeCovered)
{{ __('You chose to cover processing fees so more of your gift reaches the cause.') }}
@endif

{{ __('With gratitude,') }}<br>
{{ config('app.name') }}
</x-mail::message>
