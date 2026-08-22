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

class HealthHistoryController extends Controller
{
    /**
     * A recent assessment whose top result reaches this score is treated
     * as needing attention. Deliberately simple: one threshold, documented.
     */
    private const ATTENTION_SCORE_THRESHOLD = 50;

    /**
     * Merged, chronologically sorted timeline of a bird's assessments and
     * manual health records. Assessments are summarized (top possible
     * disease + score + severity); full diagnostic detail stays behind
     * GET /api/v1/health-assessments/{id}.
     */
    public function history(Request $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('view', $gamefowl);

        $entries = collect()
            ->merge($this->assessmentEntries($gamefowl))
            ->merge($this->recordEntries($gamefowl))
            // Numeric [timestamp, id] tuple: newest first, deterministic tie-break.
            ->sortByDesc(fn (array $entry) => $entry['sort'])
            ->values();

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
     *      (covers low scores and assessments with zero qualifying results)
     */
    public function status(Request $request, int $gamefowlId): JsonResponse
    {
        $gamefowl = $request->user()->gamefowls()->findOrFail($gamefowlId);
        $this->authorize('view', $gamefowl);

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
     * @return array{0: string, 1: int|null}
     */
    private function deriveStatus(?HealthAssessment $latest, int $recentDays): array
    {
        if (! $latest) {
            return ['no_data', null];
        }

        $daysOld = (int) floor($latest->created_at->diffInHours(now()) / 24);

        if ($daysOld > $recentDays) {
            return ['stale', $daysOld];
        }

        $topScore = $latest->results->first()?->match_score;

        return [($topScore ?? 0) >= self::ATTENTION_SCORE_THRESHOLD ? 'needs_attention' : 'healthy', $daysOld];
    }

    /**
     * Summarized assessment entries — one row per assessment, no nested results.
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
