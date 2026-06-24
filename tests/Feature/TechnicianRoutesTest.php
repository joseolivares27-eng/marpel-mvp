<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_dashboard_requires_authentication(): void
    {
        $this->get('/tecnico')->assertRedirect('/login');
    }

    public function test_technician_can_open_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'technician']);

        $this->actingAs($user)->get('/tecnico')->assertOk();
    }
}
