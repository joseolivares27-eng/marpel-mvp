@props(['installation', 'label' => 'Como llegar'])

<details class="maps-menu">
    <summary class="button">🧭 {{ $label }}</summary>
    <div class="maps-menu-panel">
        <a href="{{ $installation->wazeUrl() }}" target="_blank" rel="noreferrer">
            <span class="app-icon app-icon-waze" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="white"><circle cx="12" cy="11" r="3.4"></circle><path d="M12 2a8 8 0 0 0-8 8c0 3.2 2.1 6.7 5.6 10.4.4.4.6.6 1 1 .8.7 1.4 1.2 1.4 1.2s.6-.5 1.4-1.2c.4-.4.6-.6 1-1C17.9 16.7 20 13.2 20 10a8 8 0 0 0-8-8zm0 13a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"></path></svg>
            </span>
            Waze
        </a>
        <a
            href="{{ $installation->mapsUrl() }}"
            target="_blank"
            rel="noreferrer"
            onclick="event.preventDefault(); openGoogleMaps('{{ $installation->googleMapsAppUrl() }}', '{{ $installation->googleMapsAndroidUrl() }}', '{{ $installation->mapsUrl() }}')"
        >
            <span class="app-icon app-icon-google" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="#fff" d="M12 2C7.6 2 4 5.6 4 10c0 5.3 6.7 11.1 7.2 11.5.5.4 1.1.4 1.6 0C13.3 21.1 20 15.3 20 10c0-4.4-3.6-8-8-8z"></path><circle cx="12" cy="10" r="3" fill="#ea4335"></circle></svg>
            </span>
            Google Maps
        </a>
        <a href="{{ $installation->appleMapsUrl() }}" target="_blank" rel="noreferrer">
            <span class="app-icon app-icon-apple" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="white"><path d="M12 2C7.6 2 4 5.6 4 10c0 5.3 6.7 11.1 7.2 11.5.5.4 1.1.4 1.6 0C13.3 21.1 20 15.3 20 10c0-4.4-3.6-8-8-8zm0 11a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"></path></svg>
            </span>
            Apple Maps
        </a>
    </div>
</details>
