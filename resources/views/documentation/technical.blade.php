@extends('documentation.layout')

@use('App\Support\CodeExcerpt')

@php
    /*
     | Every code block from the codebase is read from the real file at render
     | time (App\Support\CodeExcerpt), so it can't drift from the code that
     | runs, and its line numbers are computed. The example inputs and outputs
     | further down are written into the page; TechnicalDocumentationPageTest
     | recomputes each one with the real engine and endpoint.
     */
    $engine = 'app/Services/ExpertSystem/DiagnosticEngine.php';
    $dto = 'app/Services/ExpertSystem/DiagnosisMatch.php';
    $controller = 'app/Http/Controllers/HealthAssessmentController.php';
    $formRequest = 'app/Http/Requests/StoreHealthAssessmentRequest.php';
    $engineTest = 'tests/Unit/ExpertSystem/DiagnosticEngineTest.php';

    $excerpts = [
        'route' => CodeExcerpt::lines('routes/api.php', '// Everything below requires a valid bearer token.', "[HealthAssessmentController::class, 'store']"),
        'rules' => CodeExcerpt::lines($formRequest, "'symptom_ids' => ['required'", "Rule::exists('symptoms', 'id')->where('is_active', true),", extra: 1),
        'config' => CodeExcerpt::lines('config/expertsystem.php', '// Minimum match_score', "'max_results' =>"),
        'docblock' => CodeExcerpt::lines($engine, 'Weighted symptom-matching inference engine', "the assessment milestone), not the engine's.", extra: 1, before: 1),
        'constants' => CodeExcerpt::lines($engine, 'private const SEVERITY_RANKS', 'private const VET_WARNING_MIN_RANK'),
        'signature' => CodeExcerpt::lines($engine, 'Match the submitted symptoms against the knowledge base', 'public function diagnose(array $symptomIds): Collection', before: 1),
        'step1' => CodeExcerpt::lines($engine, '// Step 1: defensive input normalization', 'return collect();', extra: 1),
        'step1b' => CodeExcerpt::lines($engine, '// array_flip builds an id => position map', '$maxResults = max(1,'),
        'step2' => CodeExcerpt::lines($engine, '$activeDiseases = Disease::query()', '->get();'),
        'step3a' => CodeExcerpt::lines($engine, '$results = [];', 'continue; // no effective rules', extra: 1),
        'step3b' => CodeExcerpt::lines($engine, "// Split this disease's rules into", 'continue; // zero overlap', extra: 1),
        'step3c' => CodeExcerpt::lines($engine, '// THE FORMULA:', 'if ($score < $threshold) {', extra: 2),
        'step3d' => CodeExcerpt::lines($engine, '$results[] = new DiagnosisMatch(', 'vetWarning: $this->shouldSurfaceVetWarning(', extra: 2),
        'step4' => CodeExcerpt::lines($engine, '// Final ranking: score DESC', 'return collect(array_slice('),
        'vetWarning' => CodeExcerpt::method($engine, 'shouldSurfaceVetWarning'),
        'dto' => CodeExcerpt::method($dto, '__construct'),
        'storeCall' => CodeExcerpt::lines($controller, 'public function store(StoreHealthAssessmentRequest $request', '$recommendations = $this->snapshotRecommendations($matches);'),
        'storePersist' => CodeExcerpt::lines($controller, '$assessment = DB::transaction(function ()', 'return $assessment;', extra: 1),
        'storeRespond' => CodeExcerpt::lines($controller, 'return response()->json([', '], 201);'),
        'resultResource' => CodeExcerpt::method('app/Http/Resources/HealthAssessmentResultResource.php', 'toArray', withDocComment: false),
        'disclaimer' => CodeExcerpt::lines('app/Http/Resources/HealthAssessmentResource.php', 'public const DISCLAIMER =', "especially for severe or critical findings.';"),
        'errorEnvelope' => CodeExcerpt::lines('bootstrap/app.php', '$exceptions->render(function (ValidationException $e, Request $request) {', '});'),
        'testSetUp' => CodeExcerpt::method($engineTest, 'setUp', withDocComment: false),
        'testHandCalc' => CodeExcerpt::method($engineTest, 'test_hand_calculated_example_against_seeded_data'),
        'testNoRules' => CodeExcerpt::method($engineTest, 'test_disease_without_rules_is_excluded_without_division_by_zero'),
        'testInactiveSymptom' => CodeExcerpt::method($engineTest, 'test_inactive_symptom_is_excluded_from_numerator_denominator_and_missing_list'),
        'testMessyInput' => CodeExcerpt::method($engineTest, 'test_unknown_and_non_numeric_symptom_ids_are_ignored_defensively'),
        'testTieBreak' => CodeExcerpt::method($engineTest, 'test_equal_scores_break_tie_alphabetically_by_disease_name'),
    ];

    // The class docblock's annotated parts: marker => the lines it covers.
    $docParts = [
        1 => CodeExcerpt::lines($engine, 'match_score(disease D, input symptoms S)', '× 100 )'),
        2 => CodeExcerpt::lines($engine, '- Only ACTIVE diseases are candidates.', '- Only ACTIVE diseases are candidates.'),
        3 => CodeExcerpt::lines($engine, '- Rules pointing to an INACTIVE symptom', 'not exist).'),
        4 => CodeExcerpt::lines($engine, '- PHP round() is used', 'no ambiguity.'),
        5 => CodeExcerpt::lines($engine, '- Diseases with no (effective) rules', 'regardless of the configured threshold.'),
        6 => CodeExcerpt::lines($engine, 'Division of responsibility:', "not the engine's."),
    ];
    $docMarkers = collect($docParts)->filter->found->mapWithKeys(fn ($part, $marker) => [$part->startLine => $marker])->all();
    $lineRange = fn (CodeExcerpt $part) => ! $part->found ? CodeExcerpt::MISSING
        : ($part->startLine === $part->endLine ? "Line {$part->startLine}" : "Lines {$part->startLine}–{$part->endLine}");

    // Worked examples. Symptom IDs are those of a freshly seeded database.
    $exampleAInput = <<<'PHP'
    // 15 = Bloody droppings, 10 = Pale comb, 21 = Lethargy or depression
    $matches = app(DiagnosticEngine::class)->diagnose([15, 10, 21]);
    PHP;

    $exampleARequest = <<<'JSON'
    {
        "symptom_ids": [15, 10, 21],
        "duration_of_symptoms": "1_to_3_days",
        "appetite": "reduced",
        "activity_level": "lethargic"
    }
    JSON;

    $exampleAMatch = <<<'PHP'
    new DiagnosisMatch(
        diseaseId: 4,
        diseaseName: 'Coccidiosis',
        matchScore: 50,
        matchedSymptoms: [
            ['id' => 10, 'name' => 'Pale comb'],
            ['id' => 15, 'name' => 'Bloody droppings'],
            ['id' => 21, 'name' => 'Lethargy or depression'],
        ],
        missingSymptoms: [
            ['id' => 11, 'name' => 'Ruffled feathers'],
            ['id' => 12, 'name' => 'Weight loss despite feeding'],
            ['id' => 16, 'name' => 'Watery white droppings'],
            ['id' => 23, 'name' => 'Huddling together'],
        ],
        severity: 'severe',
        vetWarning: null,
    )
    PHP;

    $exampleAResponse = <<<'JSON'
    {
        "success": true,
        "message": "Health assessment submitted successfully.",
        "data": {
            "id": 1,
            "gamefowl_id": 1,
            "age_at_assessment": null,
            "sex_at_assessment": "male",
            "duration_of_symptoms": "1_to_3_days",
            "appetite": "reduced",
            "activity_level": "lethargic",
            "additional_notes": null,
            "submitted_symptoms": [
                {
                    "id": 10,
                    "name": "Pale comb"
                },
                {
                    "id": 15,
                    "name": "Bloody droppings"
                },
                {
                    "id": 21,
                    "name": "Lethargy or depression"
                }
            ],
            "results": [
                {
                    "rank": 1,
                    "possible_disease": {
                        "id": 4,
                        "name": "Coccidiosis"
                    },
                    "match_score": 50,
                    "matched_symptoms": [
                        "Pale comb",
                        "Bloody droppings",
                        "Lethargy or depression"
                    ],
                    "missing_symptoms": [
                        "Ruffled feathers",
                        "Weight loss despite feeding",
                        "Watery white droppings",
                        "Huddling together"
                    ],
                    "severity_at_assessment": "severe",
                    "vet_warning_at_assessment": null,
                    "recommendations": [
                        {
                            "id": 5,
                            "title": "Keep litter dry and replace soiled bedding",
                            "content": "Wet litter promotes parasite oocysts and bacterial growth. Remove damp spots daily and top up with clean, dry bedding.",
                            "category": "hygiene"
                        },
                        {
                            "id": 2,
                            "title": "Provide clean water with electrolytes",
                            "content": "Offer fresh drinking water supplemented with electrolytes and vitamins to help birds rehydrate and recover, especially those with diarrhea or fever.",
                            "category": "nutrition"
                        },
                        {
                            "id": 7,
                            "title": "Consult a licensed veterinarian before medicating",
                            "content": "Do not give antibiotics or other medicines without proper diagnosis and dosage guidance. Wrong drugs or doses waste money and worsen resistance.",
                            "category": "medication"
                        },
                        {
                            "id": 9,
                            "title": "Monitor the flock twice daily and record new cases",
                            "content": "Check each bird morning and evening for changes in appetite, droppings, posture, and breathing. Early detection greatly improves outcomes.",
                            "category": "monitoring"
                        }
                    ]
                }
            ],
            "disclaimer": "This assessment is generated from reported symptoms and is not a confirmed veterinary diagnosis. Always consult a licensed veterinarian for confirmation and treatment, especially for severe or critical findings.",
            "created_at": "2026-09-22T01:41:00+00:00"
        }
    }
    JSON;

    $exampleBRequest = <<<'JSON'
    {
        "symptom_ids": [2]
    }
    JSON;

    $exampleBRejection = <<<'JSON'
    {
        "success": false,
        "message": "Validation failed.",
        "errors": {
            "symptom_ids.0": [
                "The selected symptom_ids.0 is invalid."
            ]
        }
    }
    JSON;

    $exampleBInput = <<<'PHP'
    // An administrator has deactivated "Huddling together" (id 23).
    Symptom::where('name', 'Huddling together')->update(['is_active' => false]);

    // 15 = Bloody droppings, 10 = Pale comb, 11 = Ruffled feathers, 21 = Lethargy
    $engine->diagnose([15, 10, 11, 21]);

    // Only the deactivated symptom
    $engine->diagnose([23]);
    PHP;

    $exampleBMatch = <<<'PHP'
    new DiagnosisMatch(
        diseaseId: 4,
        diseaseName: 'Coccidiosis',
        matchScore: 71,
        matchedSymptoms: [
            ['id' => 10, 'name' => 'Pale comb'],
            ['id' => 11, 'name' => 'Ruffled feathers'],
            ['id' => 15, 'name' => 'Bloody droppings'],
            ['id' => 21, 'name' => 'Lethargy or depression'],
        ],
        missingSymptoms: [
            ['id' => 12, 'name' => 'Weight loss despite feeding'],
            ['id' => 16, 'name' => 'Watery white droppings'],
        ],
        severity: 'severe',
        vetWarning: null,
    )
    PHP;

    $exampleCInput = <<<'PHP'
    // A string, a null, an unknown ID, and Bloody droppings (15) twice
    $engine->diagnose(['abc', null, 999999, 15, 15]);
    PHP;

    $exampleCMatch = <<<'PHP'
    new DiagnosisMatch(
        diseaseId: 4,
        diseaseName: 'Coccidiosis',
        matchScore: 21,
        matchedSymptoms: [
            ['id' => 15, 'name' => 'Bloody droppings'],
        ],
        missingSymptoms: [
            ['id' => 10, 'name' => 'Pale comb'],
            ['id' => 11, 'name' => 'Ruffled feathers'],
            ['id' => 12, 'name' => 'Weight loss despite feeding'],
            ['id' => 16, 'name' => 'Watery white droppings'],
            ['id' => 21, 'name' => 'Lethargy or depression'],
            ['id' => 23, 'name' => 'Huddling together'],
        ],
        severity: 'severe',
        vetWarning: null,
    )
    PHP;

    $envelopeShapes = <<<'TEXT'
    201 Created               { "success": true,  "message": "Health assessment submitted successfully.", "data": { … } }
    422 Unprocessable Content { "success": false, "message": "Validation failed.", "errors": { "field": ["…"] } }
    TEXT;

    // Every test in DiagnosticEngineTest, in file order, and what it pins.
    $engineTests = [
        'test_hand_calculated_example_against_seeded_data' => 'Example A: Coccidiosis 50, three matched, four missing, by name.',
        'test_second_hand_calculated_example_checks_rounding' => 'Newcastle Disease: 13 of 28 = 46.43, rounded to 46.',
        'test_multiple_matches_are_ranked_by_score_descending' => 'Seeded three-way ranking: Coccidiosis 50, Fowl Cholera 50, Newcastle 46 (the tie resolved alphabetically).',
        'test_full_symptom_set_scores_100_with_no_missing' => 'All seven Coccidiosis signs: 100, nothing missing.',
        'test_inactive_symptom_is_excluded_from_numerator_denominator_and_missing_list' => 'Example B: the deactivated rule leaves both sums (71) and the missing list.',
        'test_inactive_disease_is_excluded_even_on_perfect_match' => 'Step 2: a deactivated disease is never scored, even at 100.',
        'test_disease_without_rules_is_excluded_without_division_by_zero' => 'Step 3a: a rule-less disease is simply absent, with no error.',
        'test_duplicate_input_symptom_ids_do_not_inflate_score' => 'Step 1: an ID sent three times counts once (50, never above 100).',
        'test_unknown_and_non_numeric_symptom_ids_are_ignored_defensively' => 'Example C: messy input scores exactly like clean input (21).',
        'test_threshold_config_excludes_low_scoring_diseases' => 'Step 3c: with the cut-off at 60, a 38 is dropped and a 100 kept.',
        'test_max_results_config_limits_output' => 'Step 4: with max_results at 2, only the top two come back.',
        'test_equal_scores_break_tie_alphabetically_by_disease_name' => 'Step 4: equal scores come back A→Z.',
        'test_vet_warning_surfaced_only_from_severe_upward' => 'shouldSurfaceVetWarning(): Newcastle (critical) shows its warning; a moderate disease never does.',
    ];

    // Section navigation: anchor => [phone chip label, sidebar label].
    $toc = [
        'files' => ['Files', 'Files involved'],
        'engine' => ['DiagnosticEngine.php', 'DiagnosticEngine.php walkthrough'],
        'examples' => ['Examples', 'Input → execution → output'],
        'connected' => ['Connected files', 'Connected files beyond the engine'],
        'tests' => ['Unit tests', 'How to read the unit tests'],
    ];
    $tag = 'Technical docs';
    $backUrl = route('docs.show');
    $backLabel = 'Overview documentation';
@endphp

@section('title', 'Inside the Diagnostic Engine')
@section('description', 'Code-level walkthrough of the GAMEFOWL diagnostic engine: DiagnosticEngine.php, its connected files, worked examples, and the unit tests that pin its behaviour.')

@push('styles')
    @include('documentation.partials.technical-styles')
@endpush

@section('hero')
    <p class="eyebrow">Technical documentation</p>
    <h1>Inside the diagnostic engine</h1>
    <p class="hero-lead">
        A code-level walkthrough of <code>DiagnosticEngine.php</code> and the files around it, for readers comfortable
        with PHP and Laravel. It follows one health check through the code: the request, validation, scoring, the
        result object, what gets saved, and what the API sends back.
    </p>
    <ul class="hero-meta">
        <li><span aria-hidden="true">📄</span> Every code excerpt is read from the source file when the page loads</li>
        <li><span aria-hidden="true">✅</span> Worked examples are recomputed by the test suite</li>
        <li><span aria-hidden="true">🧩</span> Laravel 12 · PHP 8.2</li>
    </ul>
    <div class="hero-links">
        <a class="page-link" href="{{ route('docs.show') }}">← Back to Overview Documentation</a>
        <span>The plain-language version, for non-programmers</span>
    </div>
@endsection

@section('toc-foot')
    <a href="{{ route('docs.show') }}">← Back to Overview Documentation</a><br>
    Code on this page is read from the source files each time it loads.
@endsection

@section('content')

    <!-- ══ 1. Files involved ═════════════════════════════════════ -->
    <section id="files" class="doc-section">
        <p class="section-kicker">Section 1</p>
        <h2>Files involved in one assessment</h2>

        <p>
            One health check, <code>POST /api/v1/gamefowls/{gamefowlId}/health-assessments</code>, passes through these
            files in this order. The plain-language version of the same journey is
            <a href="{{ route('docs.show') }}#flow">Section 4 of the overview</a>.
        </p>

        <ol class="chain">
            <li>
                <span class="chain-num" aria-hidden="true">1</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">routes/api.php</span>
                        <span class="chain-layer layer-http">HTTP</span>
                    </div>
                    <span class="chain-symbol">Route::post(…, [HealthAssessmentController::class, 'store'])</span>
                    <p class="chain-role">Registers the endpoint inside the <code>auth:sanctum</code> group, so every request needs a bearer token.</p>
                </div>
            </li>
            <li>
                <span class="chain-num" aria-hidden="true">2</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">app/Http/Requests/StoreHealthAssessmentRequest.php</span>
                        <span class="chain-layer layer-validation">Validation</span>
                    </div>
                    <span class="chain-symbol">StoreHealthAssessmentRequest::rules()</span>
                    <p class="chain-role">
                        Validates the payload: 1–30 symptom IDs, each an existing, <em>active</em> symptom, plus optional
                        context fields. If it fails, the response is a 422 and nothing below runs.
                    </p>
                </div>
            </li>
            <li>
                <span class="chain-num" aria-hidden="true">3</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">app/Http/Controllers/HealthAssessmentController.php</span>
                        <span class="chain-layer layer-http">HTTP</span>
                    </div>
                    <span class="chain-symbol">HealthAssessmentController::store()</span>
                    <p class="chain-role">
                        Finds the caller's own bird (anyone else's is a 404), authorizes, calls the engine, copies the
                        care advice, saves everything in one transaction, and responds with 201.
                    </p>
                </div>
            </li>
            <li>
                <span class="chain-num" aria-hidden="true">4</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">app/Services/ExpertSystem/DiagnosticEngine.php</span>
                        <span class="chain-layer layer-engine">Engine</span>
                    </div>
                    <span class="chain-symbol">DiagnosticEngine::diagnose(array $symptomIds): Collection</span>
                    <p class="chain-role">
                        The inference: scores every active disease against the submitted IDs and returns ranked matches.
                        Reads the cut-off and result limit from <code>config/expertsystem.php</code>.
                    </p>
                </div>
            </li>
            <li>
                <span class="chain-num" aria-hidden="true">5</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">app/Services/ExpertSystem/DiagnosisMatch.php</span>
                        <span class="chain-layer layer-engine">Engine</span>
                    </div>
                    <span class="chain-symbol">DiagnosisMatch (readonly DTO)</span>
                    <p class="chain-role">One per candidate disease: id, name, score, matched and missing symptoms, severity, vet warning.</p>
                </div>
            </li>
            <li>
                <span class="chain-num" aria-hidden="true">6</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">app/Models/HealthAssessmentResult.php</span>
                        <span class="chain-layer layer-data">Persistence</span>
                    </div>
                    <span class="chain-symbol">HealthAssessmentResult (Eloquent model)</span>
                    <p class="chain-role">The saved snapshot: one row per ranked match, written inside <code>store()</code>'s transaction.</p>
                </div>
            </li>
            <li>
                <span class="chain-num" aria-hidden="true">7</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">app/Http/Resources/HealthAssessmentResultResource.php</span>
                        <span class="chain-layer layer-response">Response</span>
                    </div>
                    <span class="chain-symbol">HealthAssessmentResultResource::toArray()</span>
                    <p class="chain-role">
                        Turns a saved result row into its API shape: <code>possible_disease</code>, score, matched and
                        missing names, severity, vet warning, care advice.
                    </p>
                </div>
            </li>
            <li>
                <span class="chain-num" aria-hidden="true">8</span>
                <div class="chain-card">
                    <div class="chain-top">
                        <span class="chain-file">app/Http/Resources/HealthAssessmentResource.php</span>
                        <span class="chain-layer layer-response">Response</span>
                    </div>
                    <span class="chain-symbol">HealthAssessmentResource::toArray() · DISCLAIMER</span>
                    <p class="chain-role">Wraps the whole assessment, nests the ranked results, and attaches the disclaimer.</p>
                </div>
            </li>
        </ol>

        <p>The chain starts at the route, inside the token-protected group:</p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['route']])

        <p>
            Supporting files: <code>config/expertsystem.php</code> (the settings the engine reads),
            <code>app/Models/Disease.php</code> (its <code>symptoms()</code> relation carries each rule's pivot
            <code>weight</code>), <code>app/Models/DiseaseSymptomRule.php</code> (the pivot model, which casts
            <code>weight</code> to an integer), and <code>bootstrap/app.php</code> (the JSON error envelope).
        </p>

        <div class="callout">
            <span class="callout-icon" aria-hidden="true">ℹ️</span>
            <p>
                <span class="callout-title">Why validation comes before the controller body</span>
                Laravel resolves and validates a Form Request when it injects it into <code>store()</code>, before the
                method body runs. So an invalid payload is answered with a 422 before the ownership lookup even happens.
            </p>
        </div>
    </section>

    <!-- ══ 2. DiagnosticEngine.php walkthrough ═══════════════════ -->
    <section id="engine" class="doc-section">
        <p class="section-kicker">Section 2</p>
        <h2>DiagnosticEngine.php walkthrough</h2>

        <p>
            The engine is one class with one public method, <code>diagnose(array $symptomIds): Collection</code>. It
            reads the knowledge base and the config but never writes, and it knows nothing about HTTP, users, or birds.
            This section follows the file from top to bottom.
        </p>

        <h3 id="engine-docblock">The contract, as the class documents it</h3>
        <p>The class docblock states the formula and its rules. The numbered markers match the notes below it.</p>

        @include('documentation.partials.code', ['excerpt' => $excerpts['docblock'], 'markers' => $docMarkers])

        <ol class="notes">
            <li>
                <span class="marker" aria-hidden="true">1</span>
                <div>
                    <span class="note-lines">{{ $lineRange($docParts[1]) }}</span>
                    <strong>The formula.</strong> For disease D and submitted symptoms S, the top adds up the weights of
                    D's rules whose symptom is in S, the bottom adds up the weights of all of D's rules, and × 100 turns
                    the ratio into a percentage. It appears in code at <a href="#step-3c">Step 3c</a>.
                </div>
            </li>
            <li>
                <span class="marker" aria-hidden="true">2</span>
                <div>
                    <span class="note-lines">{{ $lineRange($docParts[2]) }}</span>
                    <strong>Active diseases only.</strong> That's the <code>where('is_active', true)</code> in
                    <a href="#step-2">Step 2</a>.
                </div>
            </li>
            <li>
                <span class="marker" aria-hidden="true">3</span>
                <div>
                    <span class="note-lines">{{ $lineRange($docParts[3]) }}</span>
                    <strong>Inactive symptoms leave both sums.</strong> Deactivating a symptom removes its rules
                    entirely, so the bottom of the fraction shrinks too. That's the eager-load constraint in
                    <a href="#step-2">Step 2</a>; <a href="#example-b">Example B</a> shows the effect.
                </div>
            </li>
            <li>
                <span class="marker" aria-hidden="true">4</span>
                <div>
                    <span class="note-lines">{{ $lineRange($docParts[4]) }}</span>
                    <strong>One rounding step.</strong> PHP's <code>round()</code> rounds halves away from zero, so
                    12.5 becomes 13. It runs once, at the end, on a value that is never negative.
                </div>
            </li>
            <li>
                <span class="marker" aria-hidden="true">5</span>
                <div>
                    <span class="note-lines">{{ $lineRange($docParts[5]) }}</span>
                    <strong>Two kinds of disease are never candidates</strong>, whatever the cut-off: one with no
                    effective rules (<a href="#step-3a">Step 3a</a>) and one that shares no symptom with the input
                    (<a href="#step-3b">Step 3b</a>).
                </div>
            </li>
            <li>
                <span class="marker" aria-hidden="true">6</span>
                <div>
                    <span class="note-lines">{{ $lineRange($docParts[6]) }}</span>
                    <strong>Defensive, not validating.</strong> Bad IDs are ignored here; rejecting them is the API
                    layer's job. See <a href="#validation-split">Defensive engine, validating API</a>.
                </div>
            </li>
        </ol>
        <p class="recap">In plain terms, this is the formula from <a href="{{ route('docs.show') }}#engine">Section 3 of the overview</a>.</p>

        <h3 id="engine-constants">Severity ranks</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['constants']])
        <p>
            Used only by <a href="#vet-warning"><code>shouldSurfaceVetWarning()</code></a> at the end of the class:
            severe (3) and critical (4) diseases pass their <code>vet_warning</code> through; mild and moderate never do.
        </p>

        <h3 id="engine-signature"><code>diagnose()</code>: the signature and the steps it documents</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['signature']])
        <p>
            The docblock lists four steps, and the subsections below take them one at a time. The input is typed
            <code>array&lt;int, mixed&gt;</code> on purpose: the engine accepts junk and cleans it itself. The result is
            a <code>Collection</code> of <code>DiagnosisMatch</code>, best first, and it can be empty.
        </p>

        <h3 id="step-1"><span class="step-badge">Step 1</span>Sanitize the input</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step1']])
        <p>
            <code>filter(is_numeric)</code> drops strings like <code>'abc'</code> and nulls, <code>map()</code> casts
            numeric strings to integers, <code>unique()</code> makes a symptom sent twice count once, and
            <code>values()</code> re-indexes. If nothing survives, the method returns an empty collection before touching
            the database.
        </p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step1b']])
        <p>
            <code>array_flip</code> turns <code>[15, 10, 21]</code> into <code>[15 =&gt; 0, 10 =&gt; 1, 21 =&gt; 2]</code>,
            so "was this rule's symptom submitted?" becomes an O(1) <code>isset()</code>. Both settings are read at call
            time, which lets tests change them per case, and <code>max(1, …)</code> stops a misconfigured
            <code>0</code> from returning nothing.
        </p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['config']])

        <h3 id="step-2"><span class="step-badge">Step 2</span>Load active diseases with their effective rules</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step2']])
        <p>
            Two queries in total: one for the active diseases, one eager load for their symptoms through the
            <code>disease_symptom_rules</code> pivot, which carries each rule's <code>weight</code>. The closure is what
            makes docblock point 3 true: a rule whose symptom is inactive never reaches the loop, so it is missing from
            both sums.
        </p>

        <h3 id="step-3"><span class="step-badge">Step 3</span>Score each disease</h3>
        <p>
            The loop body runs once per active disease. It has two early exits before the formula and one after it.
        </p>

        <h4 id="step-3a" class="sub-step"><span class="step-badge">3a</span>Total possible weight, and the rule-less guard</h4>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step3a']])
        <div class="guard">
            <span class="guard-label">Guard · <code>$totalWeight &lt;= 0</code></span>
            <p>
                A disease with no effective rules (none defined, or all its symptoms deactivated) totals 0 and is skipped
                here. That makes the division in <a href="#step-3c">Step 3c</a> safe by construction: it only ever runs
                with a total above zero. Step 3b would also stop a rule-less disease, since it can't match anything, but
                this guard keeps the division safe even if a zero-weight rule ever reached the database.
            </p>
        </div>

        <h4 id="step-3b" class="sub-step"><span class="step-badge">3b</span>Split matched from missing, and the zero-overlap guard</h4>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step3b']])
        <div class="guard">
            <span class="guard-label">Guard · <code>$matched-&gt;isEmpty()</code></span>
            <p>
                A disease that shares no symptom with the input would score 0. It is dropped here, so it can't appear
                even if the cut-off were set to 0. <code>$missing</code> is kept because the API returns it: it tells the
                owner which of the disease's signs weren't reported.
            </p>
        </div>

        <h4 id="step-3c" class="sub-step"><span class="step-badge">3c</span>The formula, and the cut-off</h4>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step3c']])
        <p>
            Docblock point 1, in code. <code>$matchedWeight / $totalWeight</code> is a float between 0 and 1, scaled to
            0–100 and rounded once. Scores under <code>min_match_threshold</code> (20 by default) are dropped, which is
            why a 13% match never reaches the owner.
        </p>

        <h4 id="step-3d" class="sub-step"><span class="step-badge">3d</span>Build the result object</h4>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step3d']])
        <p>
            Each surviving disease becomes a <code>DiagnosisMatch</code> holding plain arrays: symptoms are reduced to
            <code>id</code> and <code>name</code>, and no Eloquent model leaves the engine. The vet warning is only
            passed through when <a href="#vet-warning"><code>shouldSurfaceVetWarning()</code></a> allows it. The class
            itself is a readonly value object:
        </p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['dto']])

        <h3 id="step-4"><span class="step-badge">Step 4</span>Rank and limit</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['step4']])
        <div class="guard">
            <span class="guard-label">Tie-break · <code>?: strcmp(…)</code></span>
            <p>
                The comparator sorts by score, highest first. Only when two scores are equal (<code>&lt;=&gt;</code>
                returns 0, so <code>?:</code> falls through) does <code>strcmp</code> order them by name, A→Z. Without it,
                equal scores would come back in database order, which isn't guaranteed. <code>array_slice</code> then
                keeps the top <code>max_results</code> (five by default).
            </p>
        </div>

        <h3 id="vet-warning"><code>shouldSurfaceVetWarning()</code>: gating by severity</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['vetWarning']])
        <p>
            <code>SEVERITY_RANKS[$severity] ?? 0</code> maps an unknown severity to 0, which never reaches
            <code>VET_WARNING_MIN_RANK</code>, so bad data fails safe: no warning rather than an error. In the seeded
            data, Newcastle Disease (critical) and Fowl Cholera (severe) have warnings; Coccidiosis is severe but has no
            warning text, so its result carries <code>null</code>.
        </p>

        <h3 id="guards">Every edge case, and what handles it</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Situation</th>
                        <th scope="col">Handled by</th>
                        <th scope="col">Result</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Non-numeric IDs (<code>'abc'</code>, <code>null</code>)</td><td><a href="#step-1">Step 1</a> · <code>filter(is_numeric)</code></td><td>Ignored</td></tr>
                    <tr><td>The same ID more than once</td><td><a href="#step-1">Step 1</a> · <code>unique()</code></td><td>Counted once</td></tr>
                    <tr><td>Nothing left after cleaning</td><td><a href="#step-1">Step 1</a> · <code>isEmpty()</code></td><td>Empty collection, no queries</td></tr>
                    <tr><td>Unknown ID (e.g. <code>999999</code>)</td><td><a href="#step-3b">Step 3b</a> · matches no rule</td><td>Ignored</td></tr>
                    <tr><td>Inactive disease</td><td><a href="#step-2">Step 2</a> · <code>where('is_active', true)</code></td><td>Never scored</td></tr>
                    <tr><td>Inactive symptom</td><td><a href="#step-2">Step 2</a> · eager-load constraint</td><td>Rule left out of both sums</td></tr>
                    <tr><td>Disease with no effective rules</td><td><a href="#step-3a">Step 3a</a> · <code>$totalWeight &lt;= 0</code></td><td>Skipped; no division by zero</td></tr>
                    <tr><td>No symptom in common with the input</td><td><a href="#step-3b">Step 3b</a> · <code>$matched-&gt;isEmpty()</code></td><td>Skipped</td></tr>
                    <tr><td>Score under the cut-off</td><td><a href="#step-3c">Step 3c</a> · <code>$score &lt; $threshold</code></td><td>Skipped</td></tr>
                    <tr><td>Equal scores</td><td><a href="#step-4">Step 4</a> · <code>strcmp</code> tie-break</td><td>Alphabetical order</td></tr>
                    <tr><td>More matches than <code>max_results</code></td><td><a href="#step-4">Step 4</a> · <code>array_slice</code></td><td>Top five kept</td></tr>
                    <tr><td>Unknown severity text</td><td><a href="#vet-warning"><code>?? 0</code></a></td><td>No vet warning</td></tr>
                </tbody>
            </table>
        </div>

        <h3 id="validation-split">Defensive engine, validating API</h3>
        <p>
            The engine quietly ignores bad input; the API layer rejects it loudly. The rejection lives in
            <code>StoreHealthAssessmentRequest</code>:
        </p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['rules']])
        <p>
            <code>Rule::exists(…)-&gt;where('is_active', true)</code> turns an unknown or inactive symptom ID into a 422
            before the controller body runs, so over HTTP the engine never sees one (<a href="#example-b">Example B</a>
            shows both behaviours side by side). The split is deliberate:
        </p>
        <ul class="prose-list">
            <li>
                <strong>The engine stays a pure function</strong> of its input, the knowledge base, and the config: no
                request, no user, no error formatting. Its unit tests call it directly against the seeded database,
                with no HTTP involved.
            </li>
            <li>
                <strong>Validation belongs to the HTTP layer</strong>, where Form Requests and the JSON error renderer
                already handle messages, the 422 envelope, and limits like the 30-symptom cap.
            </li>
            <li>
                <strong>It's defence in depth.</strong> If validation were ever loosened, the engine still couldn't
                double-count a symptom or divide by zero, and any other caller gets the same safe behaviour.
            </li>
        </ul>
        <p class="recap">The plain-language list of these rules is in <a href="{{ route('docs.show') }}#engine">Section 3 of the overview</a>.</p>
    </section>

    <!-- ══ 3. Sample input → execution → output ══════════════════ -->
    <section id="examples" class="doc-section">
        <p class="section-kicker">Section 3</p>
        <h2>Sample input → code execution → output</h2>

        <p>
            Three worked examples on the seeded knowledge base, each reproducing an existing test. Symptom IDs are those
            of a freshly seeded database, where symptoms are inserted in a fixed order (so Bloody droppings is 15); a
            database that has been re-seeded may number them differently, which is why the tests look symptoms up by
            name.
        </p>

        <article class="example" id="example-a">
            <h3>Example A · A clean match: Coccidiosis, 50%</h3>
            <div class="example-refs">
                <span class="test-ref">DiagnosticEngineTest::test_hand_calculated_example_against_seeded_data</span>
                <span class="test-ref">HealthAssessmentTest::test_owner_can_submit_assessment_for_own_bird_and_results_match_engine</span>
            </div>
            <p class="recap">The same example, in plain language: <a href="{{ route('docs.show') }}#worked-example">Section 3 of the overview</a>.</p>

            <p class="io-label"><b>1</b> Input</p>
            @include('documentation.partials.code', ['code' => $exampleAInput, 'title' => 'Calling the engine directly'])
            @include('documentation.partials.code', ['code' => $exampleARequest, 'lang' => 'json', 'title' => 'POST /api/v1/gamefowls/1/health-assessments', 'meta' => 'request body', 'example' => 'a-request'])

            <p class="io-label"><b>2</b> What the engine does</p>
            <ol class="trace">
                <li><a href="#step-1">Step 1</a> keeps all three IDs; the lookup is <code>[15 =&gt; 0, 10 =&gt; 1, 21 =&gt; 2]</code>.</li>
                <li><a href="#step-2">Step 2</a> loads the five active diseases with all 32 rules (every seeded symptom is active).</li>
                <li><a href="#step-3">Step 3</a> scores each disease:</li>
            </ol>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Disease</th>
                            <th scope="col">Σ all rules</th>
                            <th scope="col">Σ matched</th>
                            <th scope="col">Score</th>
                            <th scope="col">Outcome</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Coccidiosis</td><td class="num">24</td><td class="num">5 + 4 + 3 = 12</td><td class="num">50</td><td>Kept → <code>DiagnosisMatch</code></td></tr>
                        <tr><td>Fowl Cholera</td><td class="num">24</td><td class="num">3</td><td class="num">12.5 → 13</td><td><a href="#step-3c">3c</a>: under 20</td></tr>
                        <tr><td>Fowl Pox</td><td class="num">16</td><td class="num">2</td><td class="num">12.5 → 13</td><td><a href="#step-3c">3c</a>: under 20</td></tr>
                        <tr><td>Infectious Coryza</td><td class="num">21</td><td class="num">0</td><td class="num">—</td><td><a href="#step-3b">3b</a>: no overlap</td></tr>
                        <tr><td>Newcastle Disease</td><td class="num">28</td><td class="num">0</td><td class="num">—</td><td><a href="#step-3b">3b</a>: no overlap</td></tr>
                    </tbody>
                </table>
            </div>
            <ol class="trace" start="4">
                <li><a href="#step-4">Step 4</a> has a single result to sort, and <code>array_slice</code> keeps it.</li>
            </ol>

            <p class="io-label"><b>3</b> Output</p>
            @include('documentation.partials.code', ['code' => $exampleAMatch, 'title' => 'diagnose() returns a Collection holding one DiagnosisMatch', 'example' => 'a-match'])
            <p>Over HTTP, <code>store()</code> saves that match as a result row, and the endpoint responds:</p>
            @include('documentation.partials.code', ['code' => $exampleAResponse, 'lang' => 'json', 'title' => '201 Created', 'meta' => 'response body', 'example' => 'a-response'])
        </article>

        <article class="example" id="example-b">
            <h3>Example B · An inactive symptom: rejected by the API, ignored by the engine</h3>
            <div class="example-refs">
                <span class="test-ref">HealthAssessmentTest::test_inactive_or_nonexistent_symptom_ids_are_rejected_here_not_by_the_engine</span>
                <span class="test-ref">DiagnosticEngineTest::test_inactive_symptom_is_excluded_from_numerator_denominator_and_missing_list</span>
            </div>
            <p>The two halves use different symptoms because they reproduce two different tests.</p>

            <div class="example-part">
                <p class="example-part-title">Over HTTP: the API rejects it</p>

                <p class="io-label"><b>1</b> Input</p>
                <p>An administrator has deactivated Sneezing (id 2). An owner submits it anyway:</p>
                @include('documentation.partials.code', ['code' => $exampleBRequest, 'lang' => 'json', 'title' => 'POST /api/v1/gamefowls/1/health-assessments', 'meta' => 'request body', 'example' => 'b-request'])

                <p class="io-label"><b>2</b> What happens</p>
                <ol class="trace">
                    <li>
                        <code>StoreHealthAssessmentRequest</code> checks <code>symptom_ids.0</code> against
                        <code>Rule::exists('symptoms', 'id')-&gt;where('is_active', true)</code> (<a href="#validation-split">excerpt</a>), and the check fails.
                    </li>
                    <li>Laravel throws a <code>ValidationException</code>, which <code>bootstrap/app.php</code> renders as the 422 envelope (<a href="#envelope">excerpt</a>).</li>
                    <li><code>store()</code> never runs, so the engine isn't called and nothing is saved.</li>
                </ol>

                <p class="io-label"><b>3</b> Output</p>
                @include('documentation.partials.code', ['code' => $exampleBRejection, 'lang' => 'json', 'title' => '422 Unprocessable Content', 'meta' => 'response body', 'example' => 'b-rejection'])
            </div>

            <div class="example-part">
                <p class="example-part-title">Calling the engine directly: it ignores it</p>

                <p class="io-label"><b>1</b> Input</p>
                @include('documentation.partials.code', ['code' => $exampleBInput, 'title' => 'Calling the engine directly'])

                <p class="io-label"><b>2</b> What the engine does</p>
                <ol class="trace">
                    <li>
                        <a href="#step-2">Step 2</a>'s constraint leaves the Huddling together rule out, so Coccidiosis has
                        six effective rules totalling <strong>21</strong> instead of 24.
                    </li>
                    <li>
                        <code>diagnose([15, 10, 11, 21])</code>: Coccidiosis matches 5 + 4 + 3 + 3 = 15, and
                        round(15 ÷ 21 × 100) = round(71.43) = <strong>71</strong>. Huddling together isn't in the missing
                        list, because for the engine that rule doesn't exist. Fowl Pox and Fowl Cholera score 13 on
                        lethargy and stop at <a href="#step-3c">3c</a>; Infectious Coryza and Newcastle Disease stop at
                        <a href="#step-3b">3b</a>.
                    </li>
                    <li>
                        <code>diagnose([23])</code>: after Step 2, no disease has a rule for symptom 23, so every disease
                        stops at <a href="#step-3b">3b</a> (<code>$matched-&gt;isEmpty()</code>), and the result is an empty
                        collection.
                    </li>
                </ol>

                <p class="io-label"><b>3</b> Output</p>
                @include('documentation.partials.code', ['code' => $exampleBMatch, 'title' => 'diagnose([15, 10, 11, 21]): one DiagnosisMatch', 'example' => 'b-match'])
                @include('documentation.partials.code', ['code' => 'collect([])', 'title' => 'diagnose([23]): an empty Collection', 'example' => 'b-empty'])
            </div>
        </article>

        <article class="example" id="example-c">
            <h3>Example C · Messy input: cleaned, not rejected</h3>
            <div class="example-refs">
                <span class="test-ref">DiagnosticEngineTest::test_unknown_and_non_numeric_symptom_ids_are_ignored_defensively</span>
            </div>

            <p class="io-label"><b>1</b> Input</p>
            @include('documentation.partials.code', ['code' => $exampleCInput, 'title' => 'Calling the engine directly'])

            <p class="io-label"><b>2</b> What the engine does</p>
            <ol class="trace">
                <li>
                    <a href="#step-1">Step 1</a>: <code>filter(is_numeric)</code> removes <code>'abc'</code> and
                    <code>null</code>, leaving <code>[999999, 15, 15]</code>; <code>unique()</code> leaves
                    <code>[999999, 15]</code>.
                </li>
                <li>No rule anywhere uses 999999, so it simply never matches.</li>
                <li>
                    Bloody droppings (15) is only on Coccidiosis' list: 5 ÷ 24 = 20.83, rounded to <strong>21</strong>, just
                    over the cut-off. Every other disease stops at <a href="#step-3b">3b</a>.
                </li>
                <li>The result is identical to <code>diagnose([15])</code>, which is exactly what the test asserts.</li>
            </ol>

            <p class="io-label"><b>3</b> Output</p>
            @include('documentation.partials.code', ['code' => $exampleCMatch, 'title' => 'diagnose() returns a Collection holding one DiagnosisMatch', 'example' => 'c-match'])
            <p>
                Over HTTP this payload never reaches the engine: <code>'abc'</code> fails the <code>integer</code> rule and
                <code>999999</code> fails <code>exists</code>, so the API answers with a 422.
            </p>
        </article>
    </section>

    <!-- ══ 4. Connected files beyond the engine ══════════════════ -->
    <section id="connected" class="doc-section">
        <p class="section-kicker">Section 4</p>
        <h2>Connected files beyond the engine</h2>

        <h3 id="controller-call">How the controller calls the engine</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['storeCall']])
        <p>
            The engine is constructor-injected, so the service container builds it. Before calling it,
            <code>store()</code> looks the bird up through the caller's own <code>gamefowls()</code> relation (another
            owner's bird gets the same 404 as a missing one), runs the policy check, and fetches symptom names for the
            snapshot. The engine call is a pure read, so it runs before any write; its result then feeds the
            transaction.
        </p>

        <h3 id="persist">Saving the snapshot</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['storePersist']])
        <p>
            Everything is written in one <code>DB::transaction</code>: the assessment row (with the bird's age and sex
            copied at that moment), the submitted symptoms with their names, and one <code>HealthAssessmentResult</code>
            per <code>DiagnosisMatch</code>, ranked by position (<code>$index + 1</code>). Each field is copied rather
            than referenced, including the disease name, severity, and vet warning, so later edits to the knowledge base
            never rewrite history. If any insert throws, nothing is kept.
            <a href="{{ route('docs.show') }}#snapshots">Why snapshots matter, in plain language →</a>
        </p>

        <h3 id="to-json">From saved row to JSON</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['resultResource']])
        <p>
            The response is built from the saved row, not from the <code>DiagnosisMatch</code>:
            <code>disease_name</code> becomes <code>possible_disease.name</code> (a result is never labelled a
            diagnosis; <a href="{{ route('docs.show') }}#possible">here's why</a>), and the matched and missing lists
            are reduced to names with <code>pluck('name')</code>. <code>DiagnosisMatch</code> has its own
            <code>toArray()</code>, but the endpoint doesn't call it; formatting the saved snapshot guarantees the API
            shows exactly what was stored.
        </p>

        <h3 id="envelope">The response envelope</h3>
        <p>Every JSON response uses one envelope. On success, <code>store()</code> wraps the resource:</p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['storeRespond']])
        <p>and <code>HealthAssessmentResource</code> attaches the disclaimer to every assessment:</p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['disclaimer']])
        <p>Validation failures are converted into the error envelope in <code>bootstrap/app.php</code>:</p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['errorEnvelope']])
        <p>So the mobile app only ever parses two shapes:</p>
        @include('documentation.partials.code', ['code' => $envelopeShapes, 'lang' => 'plaintext', 'title' => 'Envelope shapes'])
        <p>
            Full, real responses: the 201 in <a href="#example-a">Example A</a> and the 422 in
            <a href="#example-b">Example B</a>.
        </p>
    </section>

    <!-- ══ 5. How to read the unit tests ═════════════════════════ -->
    <section id="tests" class="doc-section">
        <p class="section-kicker">Section 5</p>
        <h2>How to read the unit tests</h2>

        <p>
            The engine's behaviour is pinned by <code>tests/Unit/ExpertSystem/DiagnosticEngineTest.php</code>. Each test
            seeds the real knowledge base, or adds a small fixture disease for an edge case, and calls the engine
            directly, with no HTTP involved:
        </p>
        @include('documentation.partials.code', ['excerpt' => $excerpts['testSetUp']])
        @include('documentation.partials.code', ['code' => 'php artisan test --filter=DiagnosticEngineTest', 'lang' => 'bash', 'title' => 'Run just these tests'])

        <h3 id="test-hand-calc">The hand-verified example (Example A)</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['testHandCalc']])
        <p>
            The comment at the top is Example A's arithmetic. The assertions pin the score (50), the counts (three
            matched, four missing), the severity, and the exact names on each side.
        </p>

        <h3 id="test-division">The divide-by-zero guard</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['testNoRules']])
        <p>
            It creates a disease with no rules at all, submits a real symptom, and checks that the disease is simply
            absent from the results, with no error: the <a href="#step-3a">Step 3a</a> guard at work.
        </p>

        <h3 id="test-inactive">Inactive symptoms (Example B)</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['testInactiveSymptom']])
        <p>
            It deactivates Huddling together and checks both halves of docblock point 3: the score rises to 71 because
            the total shrank to 21, the symptom disappears from the missing list, and submitting only that symptom gives
            no Coccidiosis result.
        </p>

        <h3 id="test-messy">Messy input (Example C)</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['testMessyInput']])

        <h3 id="test-tie">The tie-break</h3>
        @include('documentation.partials.code', ['excerpt' => $excerpts['testTieBreak']])
        <p>
            Two fixture diseases share a single rule of the same weight, so both score 100. The test pins that "Alpha
            Condition" comes before "Zulu Condition" (the <a href="#step-4">Step 4</a> tie-break).
        </p>

        <h3 id="test-list">All engine tests at a glance</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Test</th>
                        <th scope="col">What it pins</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($engineTests as $name => $pins)
                        <tr><td><code>{{ $name }}</code></td><td>{{ $pins }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="callout callout-summary">
            <span class="callout-icon" aria-hidden="true">✅</span>
            <p>
                <span class="callout-title">Why the numbers on this page can be trusted</span>
                <code>HealthAssessmentTest</code> asserts the endpoint returns exactly what <code>diagnose()</code> returns
                for the same input, and <code>TechnicalDocumentationPageTest</code> recomputes every example on this page
                with the real engine and endpoint. The code excerpts are read from the files themselves.
            </p>
        </div>

        <p>
            <a class="page-link" href="{{ route('docs.show') }}">← Back to Overview Documentation</a>
        </p>
    </section>

@endsection

@push('scripts')
    {{-- Syntax colouring for this page only (highlight.js, common-languages
         build: PHP, JSON, Bash). Pinned by SRI hash. If it can't load, the code
         stays readable in plain colours. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.2/highlight.min.js"
        integrity="sha384-pZ4gLQJIEk/VKnGk59xUSQK8Ne/erZQR9cpnPXPLB9iN1tXsVkRVfHYGdFYrSkv1"
        crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            if (!window.hljs) {
                return;
            }

            document.querySelectorAll('.code-pre code').forEach(function (block) {
                window.hljs.highlightElement(block);
            });
        });
    </script>
@endpush
