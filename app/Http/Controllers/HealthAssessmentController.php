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

class HealthAssessmentController extends Controller
{
    public function __construct(private DiagnosticEngine $engine)
    {
    }

    /**
     * Assessments are immutable: created once, never edited or deleted.
     * Everything below runs in one transaction so a failure mid-persist
     * can never leave a partial assessment behind.
     */
    public function store(StoreHealthAssessmentRequest $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('create', [HealthAssessment::class, $gamefowl]);

        $validated = $request->validated();
        $symptomIds = array_map('intval', $validated['symptom_ids']);
        $symptomNames = Symptom::whereIn('id', $symptomIds)->pluck('name', 'id');
        $matches = $this->engine->diagnose($symptomIds);

        $assessment = DB::transaction(function () use ($gamefowl, $validated, $symptomIds, $symptomNames, $matches) {
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

            $assessment->symptoms()->attach(
                $symptomNames->map(fn (string $name, int $id) => ['symptom_name' => $name])->all()
            );

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
     * Snapshot the bird's current age as a human-readable string so the
     * assessment stays accurate even after the bird ages or its DOB is
     * later corrected.
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
