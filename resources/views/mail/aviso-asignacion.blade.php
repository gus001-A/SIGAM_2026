<x-mail::message>
# {{ $titulo }}

{{ $cuerpo }}

<x-mail::button :url="$url">
Ver detalle
</x-mail::button>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
