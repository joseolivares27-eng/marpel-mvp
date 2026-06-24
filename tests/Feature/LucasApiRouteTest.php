<?php

namespace Tests\Feature;

use Tests\TestCase;

class LucasApiRouteTest extends TestCase
{
    public function test_lucas_notice_endpoint_requires_api_token(): void
    {
        $this->postJson('/api/lucas/avisos', [])->assertUnauthorized()->assertJson([
            'success' => false,
            'mensaje' => 'Token de API no valido.',
        ]);
    }
}
