<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * File: tests/Feature/ExampleTest.php
 *
 * Purpose:
 *   Framework smoke test: confirms the default "/" route still returns a
 *   successful response after all our changes. Cheap canary for routing
 *   or bootstrap-level breakage.
 */
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
