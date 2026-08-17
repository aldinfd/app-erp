@component('mail::message')
# {{ $title }}

{{ $body }}

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
