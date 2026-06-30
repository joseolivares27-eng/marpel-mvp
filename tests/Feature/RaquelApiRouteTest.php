<?php

namespace Tests\Feature;

use Tests\TestCase;

class RaquelApiRouteTest extends TestCase
{
    public function test_raquel_notice_endpoint_requires_api_token(): void
    {
        $this->postJson('/api/raquel/avisos', [])->assertUnauthorized()->assertJson([
            'success' => false,
            'mensaje' => 'Token de API no valido.',
        ]);
    }
}
