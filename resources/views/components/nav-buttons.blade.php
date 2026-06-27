@props(['installation', 'label' => 'Waze'])

<a class="button" href="{{ $installation->wazeUrl() }}" target="_blank" rel="noreferrer">🧭 {{ $label }}</a>
<a
    class="button secondary"
    href="{{ $installation->mapsUrl() }}"
    target="_blank"
    rel="noreferrer"
    onclick="event.preventDefault(); openGoogleMaps('{{ $installation->googleMapsAppUrl() }}', '{{ $installation->googleMapsAndroidUrl() }}', '{{ $installation->mapsUrl() }}')"
>🗺️ Google Maps</a>
<a class="button secondary" href="{{ $installation->appleMapsUrl() }}" target="_blank" rel="noreferrer">🍎 Apple Maps</a>
