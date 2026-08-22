<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * File: tests/TestCase.php
 *
 * Purpose:
 *   Base class for ALL test suites. Every test extends this, which bootstraps
 *   a full Laravel application per test method (fresh container, fresh
 *   in-memory database via RefreshDatabase where used) and provides the HTTP
 *   testing helpers ($this->postJson(), ->assertOk(), etc.).
 */
abstract class TestCase extends BaseTestCase
{
    //
}
