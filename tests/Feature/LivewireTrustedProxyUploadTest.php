<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LivewireTrustedProxyUploadTest extends TestCase
{
    public function test_livewire_upload_signed_url_uses_forwarded_https_host(): void
    {
        Route::get('/_test/livewire-signed-upload-url', fn (): array => [
            'url' => URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5)),
        ]);

        $server = [
            'REMOTE_ADDR' => '172.19.0.10',
            'HTTP_HOST' => 'crm.automatismosmarpel.com',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
            'HTTP_X_FORWARDED_HOST' => 'crm.automatismosmarpel.com',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ];

        $signedUrl = $this
            ->withServerVariables($server)
            ->get('/_test/livewire-signed-upload-url')
            ->assertOk()
            ->json('url');

        $this->assertStringStartsWith('https://crm.automatismosmarpel.com/livewire/upload-file', $signedUrl);

        $uploadUri = parse_url($signedUrl, PHP_URL_PATH).'?'.parse_url($signedUrl, PHP_URL_QUERY);

        $this
            ->withServerVariables($server)
            ->post($uploadUri, ['files' => []])
            ->assertOk()
            ->assertJson(['paths' => []]);
    }
}
