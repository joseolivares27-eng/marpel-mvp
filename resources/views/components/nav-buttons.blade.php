@props(['installation', 'label' => 'Como llegar'])

<details class="maps-menu">
    <summary class="button">🧭 {{ $label }}</summary>
    <div class="maps-menu-panel">
        <a href="{{ $installation->wazeUrl() }}" target="_blank" rel="noreferrer">🧭 Waze</a>
        <a
            href="{{ $installation->mapsUrl() }}"
            target="_blank"
            rel="noreferrer"
            onclick="event.preventDefault(); openGoogleMaps('{{ $installation->googleMapsAppUrl() }}', '{{ $installation->googleMapsAndroidUrl() }}', '{{ $installation->mapsUrl() }}')"
        >🗺️ Google Maps</a>
        <a href="{{ $installation->appleMapsUrl() }}" target="_blank" rel="noreferrer">🍎 Apple Maps</a>
    </div>
</details>
