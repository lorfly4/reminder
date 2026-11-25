<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_api_health_endpoint_returns_consistent_json(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.service', 'remindly-api')
            ->assertJsonStructure([
                'data' => ['status', 'service', 'timestamp'],
                'meta',
                'message',
            ]);
    }
}
