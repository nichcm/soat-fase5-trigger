<?php

namespace Tests\Feature;

use Tests\TestCase;

class PingTest extends TestCase
{
    public function test_ping_retorna_pong(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertStatus(200)
            ->assertJsonStructure(['err', 'msg', 'service'])
            ->assertJson(['err' => false, 'msg' => 'pong']);
    }
}
