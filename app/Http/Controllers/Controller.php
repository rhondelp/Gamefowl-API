<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * File: app/Http/Controllers/Controller.php
 *
 * Purpose:
 *   The abstract base class EVERY controller extends. Its only job is to
 *   give all controllers shared framework abilities.
 *
 * How it fits into the project:
 *   The AuthorizesRequests trait provides the `$this->authorize('ability',
 *   $model)` calls you see throughout the resource controllers. It checks
 *   the matching method on a Policy class (e.g. GamefowlPolicy) and throws
 *   an exception when denied, which our bootstrap error renderer turns
 *   into a uniform 404 for API routes (anti-enumeration).
 *
 * Note: Laravel 12 ships this class EMPTY — the trait was added here
 * explicitly during Milestone 3, so policy authorization works everywhere.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
