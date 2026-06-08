<x-mail::message>
# {{ __('New Contact Form Submission') }}

**{{ __('Name') }}:** {{ $contactMessage->fullname }}

**{{ __('Email') }}:** {{ $contactMessage->email }}

@if ($contactMessage->phone)
**{{ __('Phone') }}:** {{ $contactMessage->phone }}
@endif

**{{ __('Subject') }}:** {{ $contactMessage->subject }}

**{{ __('Message') }}:**

{{ $contactMessage->message }}

</x-mail::message>
