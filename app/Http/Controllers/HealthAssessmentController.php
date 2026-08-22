<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHealthAssessmentRequest;
use App\Http\Resources\HealthAssessmentResource;
use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\Symptom;
use App\Services\ExpertSystem\DiagnosticEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * File: app/Http/Controllers/HealthAssessmentController.php
 *
 * Purpose:
 *   The heart of the product flow: an owner reports symptoms for one of
 *   their birds, the DiagnosticEngine scores possible diseases, everything
 *   is persisted as an immutable record, and ranked results are returned.
 *
 *   POST /api/v1/gamefowls/{gamefowlId}/health-assessments  — submit + score
 *   GET  /api/v1/health-assessments/{id}                    — full detail
 *
 * How it fits into the project:
 *   This controller CONSUMES the engine (App\Services\ExpertSystem\
 *   DiagnosticEngine) but never modifies its scoring. Input validation
 *   (do the symptom IDs exist and are they active?) lives in
 *   StoreHealthAssessmentRequest — that responsibility was deliberately
 *   split away from the engine during Milestone 5.
 *
 * Immutability: there are NO update/delete endpoints for assessments.
 * They are historical medical-style records; snapshots keep them accurate
 * forever even if the knowledge base changes later.
 */
class HealthAssessmentController extends Controller
{
    /**
     * The engine is injected by Laravel's service container (see the
     * constructor), so tests can swap it and controllers never build it
     * manually.
     */
    public function __construct(private DiagnosticEngine $engine)
    {
    }

    /**
     * Submit symptoms for a bird and store the full assessment.
     *
     * Flow, step by step:
     *  1. Resolve the bird through the owner's relationship -> another
     *     owner's bird (or an unknown ID) is a generic 404, then authorize.
     *  2. Validate input via StoreHealthAssessmentRequest (symptom IDs must
     *     exist AND be active).
     *  3. Run the DiagnosticEngine to get ranked matches.
     *  4. In ONE database transaction: create the assessment row, attach
     *     the chosen symptoms WITH name snapshots, and save one result row
     *     per ranked disease. If anything throws mid-way, the transaction
     *     rolls back everything (tested: no partial rows survive).
     *  5. Return the complete record via HealthAssessmentResource (201).
     */
    public function store(StoreHealthAssessmentRequest $request, int $gamefowlId): JsonResponse
    {
        // Ownership-scoped lookup: foreign or missing bird => same 404.
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('create', [HealthAssessment::class, $gamefowl]);

        $validated = $request->validated();
        $symptomIds = array_map('intval', $validated['symptom_ids']);

        // Name snapshots for the pivot table, resolved once up front.
        $symptomNames = Symptom::whereIn('id', $symptomIds)->pluck('name', 'id');

        // The engine is called BEFORE writes begin; its output then feeds
        // the transaction below.
        $matches = $this->engine->diagnose($symptomIds);

        $assessment = DB::transaction(function () use ($gamefowl, $validated, $symptomIds, $symptomNames, $matches) {
            // Snapshot age/sex: client values win if provided; otherwise we
            // copy from the live bird right now ("at assessment" semantics).
            $assessment = $gamefowl->healthAssessments()->create([
                'age_at_assessment' => $validated['age_at_assessment']
                    ?? $this->snapshotAge($gamefowl),
                'sex_at_assessment' => $validated['sex_at_assessment']
                    ?? $gamefowl->sex,
                'duration_of_symptoms' => $validated['duration_of_symptoms'] ?? null,
                'appetite' => $validated['appetite'] ?? null,
                'activity_level' => $validated['activity_level'] ?? null,
                'additional_notes' => $validated['additional_notes'] ?? null,
            ]);

            // attach() with id => attributes fills the snapshot column on
            // each pivot row (plain attach([ids]) would leave it NULL).
            $assessment->symptoms()->attach(
                $symptomNames->map(fn (string $name, int $id) => ['symptom_name' => $name])->all()
            );

            // One row per ranked match; rank = position in the engine's
            // sorted output (1 = best). All fields are snapshots.
            foreach ($matches as $index => $match) {
                $assessment->results()->create([
                    'disease_id' => $match->diseaseId,
                    'disease_name' => $match->diseaseName,
                    'rank' => $index + 1,
                    'match_score' => $match->matchScore,
                    'matched_symptoms' => $match->matchedSymptoms,
                    'missing_symptoms' => $match->missingSymptoms,
                    'severity_at_assessment' => $match->severity,
                    'vet_warning_at_assessment' => $match->vetWarning,
                ]);
            }

            return $assessment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Health assessment submitted successfully.',
            'data' => new HealthAssessmentResource($assessment->fresh(['results', 'symptoms'])),
        ], 201);
    }

    /**
     * Full detail of one assessment (submitted context, selected symptoms,
     * all ranked results with explanations).
     *
     * Authorization goes through HealthAssessmentPolicy::view, which simply
     * delegates to GamefowlPolicy's ownership check — one shared rule for
     * "may this user see data about this bird?".
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $assessment = HealthAssessment::with(['results', 'symptoms', 'gamefowl'])->findOrFail($id);
        $this->authorize('view', $assessment);

        return response()->json([
            'success' => true,
            'message' => 'Health assessment retrieved successfully.',
            'data' => new HealthAssessmentResource($assessment),
        ]);
    }

    /**
     * Build a human-readable age string ("2y 3m") from the bird's birth
     * date, used as the assessment's age snapshot when the client doesn't
     * supply one. Returns null when the birth date is unknown — matching
     * the nullable column.
     */
    private function snapshotAge(Gamefowl $gamefowl): ?string
    {
        if (! $gamefowl->date_of_birth) {
            return null;
        }

        $interval = $gamefowl->date_of_birth->diff(now());

        return "{$interval->y}y {$interval->m}m";
    }
}
