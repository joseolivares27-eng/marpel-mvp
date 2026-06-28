<?php

namespace App\Services;

use App\Models\GoogleCalendarToken;
use App\Models\Notice;
use App\Models\Review;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarApi;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;

class GoogleCalendarService
{
    public function isConnected(): bool
    {
        return GoogleCalendarToken::query()->exists();
    }

    public function getAuthUrl(): string
    {
        return $this->makeClient()->createAuthUrl();
    }

    public function handleAuthCallback(string $code): void
    {
        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);

        GoogleCalendarToken::query()->delete();

        GoogleCalendarToken::create([
            'access_token' => json_encode($token),
            'refresh_token' => $token['refresh_token'] ?? null,
            'expires_at' => isset($token['created']) && isset($token['expires_in'])
                ? now()->setTimestamp($token['created'] + $token['expires_in'])
                : null,
        ]);
    }

    public function disconnect(): void
    {
        GoogleCalendarToken::query()->delete();
    }

    public function pushEvent(Notice|Review $model): void
    {
        if (! $model->scheduled_at || ! $this->isConnected()) {
            return;
        }

        $service = $this->getCalendarApi();
        $calendarId = config('services.google_calendar.calendar_id');

        $event = new GoogleEvent([
            'summary' => $this->buildSummary($model),
            'description' => $this->buildDescription($model),
            'start' => new EventDateTime([
                'dateTime' => $model->scheduled_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $model->scheduled_at->copy()->addHour()->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]),
        ]);

        if ($model->google_event_id) {
            try {
                $service->events->update($calendarId, $model->google_event_id, $event);

                return;
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() !== 404) {
                    throw $e;
                }
                // El evento ya no existe en Google Calendar: lo recreamos.
            }
        }

        $created = $service->events->insert($calendarId, $event);
        $model->forceFill(['google_event_id' => $created->getId()])->saveQuietly();
    }

    public function deleteEvent(Notice|Review $model): void
    {
        if (! $model->google_event_id || ! $this->isConnected()) {
            return;
        }

        try {
            $this->getCalendarApi()->events->delete(
                config('services.google_calendar.calendar_id'),
                $model->google_event_id
            );
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() !== 404 && $e->getCode() !== 410) {
                throw $e;
            }
        }
    }

    /**
     * @return array<int, array{id: string, title: string, start: \Carbon\CarbonInterface|null, htmlLink: string}>
     */
    public function pullUpcomingEvents(int $daysAhead = 30): array
    {
        if (! $this->isConnected()) {
            return [];
        }

        $service = $this->getCalendarApi();

        $events = $service->events->listEvents(config('services.google_calendar.calendar_id'), [
            'timeMin' => now()->toRfc3339String(),
            'timeMax' => now()->addDays($daysAhead)->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'maxResults' => 50,
        ]);

        return collect($events->getItems())
            ->map(fn (GoogleEvent $event) => [
                'id' => $event->getId(),
                'title' => $event->getSummary() ?: '(sin titulo)',
                'start' => $event->getStart()?->getDateTime()
                    ? \Carbon\Carbon::parse($event->getStart()->getDateTime())
                    : null,
                'htmlLink' => $event->getHtmlLink(),
            ])
            ->all();
    }

    protected function buildSummary(Notice|Review $model): string
    {
        $installation = $model->installation?->name ?? 'Sin instalacion';

        return $model instanceof Notice
            ? "Aviso: {$installation}"
            : "Revision: {$installation}";
    }

    protected function buildDescription(Notice|Review $model): string
    {
        $customer = $model->customer?->legal_name ?? 'Sin cliente';
        $technician = $model->technician?->name ?? 'Sin asignar';

        return "Cliente: {$customer}\nTecnico: {$technician}";
    }

    protected function getCalendarApi(): GoogleCalendarApi
    {
        return new GoogleCalendarApi($this->getAuthenticatedClient());
    }

    protected function getAuthenticatedClient(): GoogleClient
    {
        $client = $this->makeClient();
        $tokenRow = GoogleCalendarToken::query()->latest('id')->first();

        if (! $tokenRow) {
            throw new \RuntimeException('Google Calendar no esta conectado.');
        }

        $token = json_decode($tokenRow->access_token, true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $refreshToken = $client->getRefreshToken() ?: $tokenRow->refresh_token;

            if (! $refreshToken) {
                throw new \RuntimeException('Google Calendar necesita reconectarse (sin refresh token).');
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            $newToken['refresh_token'] = $newToken['refresh_token'] ?? $refreshToken;

            $tokenRow->update([
                'access_token' => json_encode($newToken),
                'refresh_token' => $newToken['refresh_token'],
                'expires_at' => isset($newToken['created']) && isset($newToken['expires_in'])
                    ? now()->setTimestamp($newToken['created'] + $newToken['expires_in'])
                    : null,
            ]);
        }

        return $client;
    }

    protected function makeClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google_calendar.client_id'));
        $client->setClientSecret(config('services.google_calendar.client_secret'));
        $client->setRedirectUri(config('services.google_calendar.redirect_uri'));
        $client->addScope(GoogleCalendarApi::CALENDAR_EVENTS);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }
}
