<?php

namespace App\Http\Controllers;

use App\Http\Resources\HealthAssessmentResource;
use App\Http\Resources\HealthHistoryEntryResource;
use App\Http\Resources\HealthRecordResource;
use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\HealthRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * File: app/Http/Controllers/HealthHistoryController.php
 *
 * Purpose:
 *   Read-oriented endpoints that give an owner a picture of one bird's
 *   health over time:
 *
 *     GET /api/v1/gamefowls/{id}/health-history  — merged timeline of
 *         assessments (summarized) + manual records, newest first
 *     GET /api/v1/gamefowls/{id}/health-status   — lightweight current
 *         status label + supporting context
 *
 * How it fits into the project:
 *   Both sources already exist elsewhere (assessments from
 *   HealthAssessmentController, manual entries from HealthRecordController);
 *   this controller only MERGES and SUMMARIZES them for display. It never
 *   duplicates full assessment detail — each timeline entry links by ID to
 *   GET /health-assessments/{id} instead.
 */
class HealthHistoryController extends Controller
{
    /**
     * A recent assessment whose top result reaches this score is treated as
     * "needs attention" by the status endpoint. One constant, documented,
     * easy to audit (and asked about during panel defense).
     */
    private const ATTENTION_SCORE_THRESHOLD = 50;

    /**
     * Merged, chronologically sorted timeline.
     *
     * Merge approach (deliberate): run two small scoped queries, normalize
     * every row into a tagged entry array, then merge/sort in PHP. A raw SQL
     * UNION was rejected because the two tables have different shapes — we
     * would still need per-row normalization afterwards, so the UNION adds
     * complexity without saving anything. Per-bird data volumes are small
     * enough that in-memory handling is fine; pagination slices the merged
     * collection manually with standard page/per_page query params.
     */
    public function history(Request $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('view', $gamefowl);

        $entries = collect()
            ->merge($this->assessmentEntries($gamefowl))
            ->merge($this->recordEntries($gamefowl))
            // Numeric [timestamp, id] tuple: newest first, deterministic
            // tie-break when two entries share a timestamp.
            ->sortByDesc(fn (array $entry) => $entry['sort'])
            ->values();

        // Manual slice-pagination over the merged feed.
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));
        $items = $entries->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'message' => 'Health history retrieved successfully.',
            'data' => [
                'items' => HealthHistoryEntryResource::collection($items),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $entries->count(),
                ],
            ],
        ]);
    }

    /**
     * Current-status summary. Label derivation rules, evaluated in order:
     *
     *   1. No assessments at all            -> "no_data"
     *      (manual records may exist; symptom screening simply has not happened)
     *   2. Latest assessment older than the configured recent window -> "stale"
     *   3. Top result match_score >= 50     -> "needs_attention"
     *   4. Otherwise                        -> "healthy"
     *      (covers low scores AND assessments where nothing matched strongly)
     *
     * These exact rules are pinned by the table-driven test in
     * tests/Feature/HealthHistoryTest::test_status_label_derivation_table.
     */
    public function status(Request $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('view', $gamefowl);

        // Config-driven so the product can tune "how old is too old".
        $recentDays = max(1, (int) config('expertsystem.recent_assessment_days', 14));

        /** @var HealthAssessment|null $latest */
        $latest = $gamefowl->healthAssessments()
            ->with('results')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $latestRecord = $gamefowl->healthRecords()
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        [$status, $daysOld] = $this->deriveStatus($latest, $recentDays);

        // Rank #1 result = what the engine considered most likely. May be
        // null if no disease cleared the threshold for that submission.
        $topResult = $latest?->results->first();

        return response()->json([
            'success' => true,
            'message' => 'Health status retrieved successfully.',
            'data' => [
                'status' => $status,
                'recent_window_days' => $recentDays,
                'based_on' => $topResult ? [
                    'assessment_id' => $latest->id,
                    'assessed_at' => $latest->created_at?->toIso8601String(),
                    'top_possible_disease' => [
                        'id' => $topResult->disease_id,
                        'name' => $topResult->disease_name,
                    ],
                    'match_score' => $topResult->match_score,
                ] : null,
                'days_since_last_assessment' => $daysOld,
                'latest_health_record' => $latestRecord ? new HealthRecordResource($latestRecord) : null,
                'disclaimer' => HealthAssessmentResource::DISCLAIMER,
            ],
        ]);
    }

    /**
     * Apply the documented label rules (see status() docblock).
     *
     * @param  HealthAssessment|null  $latest  newest assessment, or null
     * @param  int  $recentDays  how many days before an assessment is stale
     * @return array{0: string, 1: int|null} [label, days since latest]
     */
    private function deriveStatus(?HealthAssessment $latest, int $recentDays): array
    {
        // Rule 1: nothing screened yet.
        if (! $latest) {
            return ['no_data', null];
        }

        // Age in whole days since the assessment was submitted.
        $daysOld = (int) floor($latest->created_at->diffInHours(now()) / 24);

        // Rule 2: data too old to trust as "current".
        if ($daysOld > $recentDays) {
            return ['stale', $daysOld];
        }

        // Rule 3/4: judge purely by the top ranked score. A missing top
        // result counts as 0 (nothing matched strongly) -> healthy.
        $topScore = $latest->results->first()?->match_score;

        return [($topScore ?? 0) >= self::ATTENTION_SCORE_THRESHOLD ? 'needs_attention' : 'healthy', $daysOld];
    }

    /**
     * Build summarized timeline entries for all of the bird's assessments.
     * Only identification data is included (top disease + score + severity);
     * full detail stays behind GET /health-assessments/{id}.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function assessmentEntries(Gamefowl $gamefowl): Collection
    {
        return $gamefowl->healthAssessments()
            ->with('results')
            ->get()
            ->map(fn (HealthAssessment $assessment) => [
                'type' => 'assessment',
                'id' => $assessment->id,
                'occurred_at' => $assessment->created_at,
                // Sort tuple: creation time first, id breaks ties.
                'sort' => [$assessment->created_at->timestamp, $assessment->id],
                'top_possible_disease' => ($top = $assessment->results->first()) ? [
                    'id' => $top->disease_id,
                    'name' => $top->disease_name,
                ] : null,
                'match_score' => $top?->match_score,
                'severity_at_assessment' => $top?->severity_at_assessment,
            ]);
    }

    /**
     * Build timeline entries for all manual logbook records. Records use
     * recorded_at (the backdated event date), not created_at, as their place
     * in history; endOfDay() makes a same-day record sort above assessments
     * logged earlier that day (deterministic, documented behavior).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function recordEntries(Gamefowl $gamefowl): Collection
    {
        return $gamefowl->healthRecords()->get()->map(fn (HealthRecord $record) => [
            'type' => 'health_record',
            'id' => $record->id,
            'occurred_at' => $record->recorded_at->startOfDay(),
            'sort' => [$record->recorded_at->endOfDay()->timestamp, $record->id],
            'record_type' => $record->type,
            'title' => $record->title,
            'weight' => $record->weight !== null ? (float) $record->weight : null,
        ]);
    }
}
