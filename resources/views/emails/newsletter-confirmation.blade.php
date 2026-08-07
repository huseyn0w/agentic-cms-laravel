@component('mail::message')
# {{ __('default/newsletter.email_heading') }}

{{ __('default/newsletter.email_intro') }}

@component('mail::button', ['url' => $confirmUrl])
{{ __('default/newsletter.email_button') }}
@endcomponent

{{ __('default/newsletter.email_fallback') }}

[{{ $confirmUrl }}]({{ $confirmUrl }})

{{ __('default/newsletter.email_ignore') }}

@endcomponent
