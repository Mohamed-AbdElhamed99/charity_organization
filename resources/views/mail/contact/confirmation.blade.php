<x-mail::message>
# {{ __('Thank you for contacting us') }}

{{ __('Dear :name,', ['name' => $contactMessage->fullname]) }}

{{ __('We have received your message regarding ":subject" and will get back to you as soon as possible.', ['subject' => $contactMessage->subject]) }}

{{ __('Thank you for reaching out to us.') }}

{{ config('app.name') }}
</x-mail::message>
