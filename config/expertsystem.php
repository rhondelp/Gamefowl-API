<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expert System Configuration
    |--------------------------------------------------------------------------
    |
    | Tunables for the DiagnosticEngine service.
    |
    */

    // Minimum match_score (0-100) a disease must reach to appear in results.
    'min_match_threshold' => env('EXPERTSYSTEM_MIN_MATCH_THRESHOLD', 20),

    // Maximum number of ranked disease matches returned per diagnosis.
    'max_results' => env('EXPERTSYSTEM_MAX_RESULTS', 5),
];
