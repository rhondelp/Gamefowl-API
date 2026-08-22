<?php

namespace App\Services\Admin;

use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\HealthAssessmentResult;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * File: app/Services/Admin/DashboardService.php
 *
 * Purpose:
 *   All query logic behind GET /api/v1/admin/dashboard. Each statistic is
 *   its own small method so every number can be tested independently
 *   (see tests/Feature/Admin/AdminDashboardTest.php) and new stats can be
 *   added without touching existing ones.
 *
 * How it fits into the project:
 *   AdminDashboardController calls these methods and wraps the results in
 *   the standard success envelope. Nothing here mutates data — read-only.
 */
class DashboardService
{
    /**
     * Definition note: a disease counts as "suggested" every time it
     * appears ANYWHERE in an assessment's ranked results — not only at
     * rank #1. This measures how often the engine surfaces each disease
     * overall; the rank-#1 view would answer a different question.
     */
    public function totalUsers(): int
    {
        // withTrashed(): include deactivated accounts so this headline always
        // equals active + inactive from usersByActiveStatus().
        return User::withTrashed()->count();
    }

    /**
     * Account count per role ('owner'/'admin'), including deactivated users.
     * Returns a collection shaped like { role: count } for direct JSON use.
     *
     * @return Collection<string, int>
     */
    public function usersByRole(): Collection
    {
        return User::withTrashed()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');
    }

    /**
     * Active vs deactivated account counts.
     * "Active" = deleted_at IS NULL; "inactive" = soft-deleted accounts only.
     *
     * @return Collection<string, int>
     */
    public function usersByActiveStatus(): Collection
    {
        $inactive = (int) User::onlyTrashed()->count();

        return collect([
            'active' => User::count(),
            'inactive' => $inactive,
        ]);
    }

    /**
     * Total birds in the system, counting ACTIVE birds only — consistent
     * with owner-facing listings where soft-deleted birds are invisible.
     */
    public function totalGamefowls(): int
    {
        return Gamefowl::count();
    }

    /**
     * Total assessments ever submitted system-wide. Assessments are
     * immutable and never deleted, so this only grows.
     */
    public function totalAssessments(): int
    {
        return HealthAssessment::count();
    }

    /**
     * The symptoms owners report most often across ALL assessments.
     *
     * How it works: joins the assessment-symptom pivot to symptoms for names,
     * counts rows per symptom, sorts by count descending then name ascending
     * (deterministic tie-break). $limit defaults to 5.
     *
     * @return Collection<int, object{id: int, name: string, report_count: int}>
     */
    public function topReportedSymptoms(int $limit = 5): Collection
    {
        return DB::table('health_assessment_symptoms')
            ->join('symptoms', 'symptoms.id', '=', 'health_assessment_symptoms.symptom_id')
            ->select('symptoms.id', 'symptoms.name', DB::raw('COUNT(*) as report_count'))
            ->groupBy('symptoms.id', 'symptoms.name')
            ->orderByDesc('report_count')
            ->orderBy('symptoms.name')
            ->limit($limit)
            ->get();
    }

    /**
     * The diseases suggested most often across ALL assessment results.
     *
     * Uses the SNAPSHOT column (disease_name) from results rather than
     * joining live diseases for display, but groups by diseases.id too so a
     * renamed disease still aggregates as one entry.
     *
     * @return Collection<int, object{id: int, name: string, suggestion_count: int}>
     */
    public function topSuggestedDiseases(int $limit = 5): Collection
    {
        return HealthAssessmentResult::query()
            ->join('diseases', 'diseases.id', '=', 'health_assessment_results.disease_id')
            ->select('diseases.id', 'health_assessment_results.disease_name as name', DB::raw('COUNT(*) as suggestion_count'))
            ->groupBy('diseases.id', 'health_assessment_results.disease_name')
            ->orderByDesc('suggestion_count')
            ->orderBy('health_assessment_results.disease_name')
            ->limit($limit)
            ->get();
    }

    /**
     * The newest assessments across all owners, summarized for a list view.
     *
     * Each entry includes the bird's name and owner id (for drill-down) plus
     * the rank-#1 result only — full nested detail lives in each assessment's
     * own endpoint, deliberately not duplicated here.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function recentAssessments(int $limit = 10): Collection
    {
        return HealthAssessment::query()
            ->with(['results', 'gamefowl'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (HealthAssessment $assessment) => [
                'id' => $assessment->id,
                'gamefowl_id' => $assessment->gamefowl_id,
                'gamefowl_name' => $assessment->gamefowl->name,
                'owner_id' => $assessment->gamefowl->user_id,
                // Top result = rank #1 row; may be null if nothing matched.
                'top_possible_disease' => ($top = $assessment->results->first()) ? [
                    'id' => $top->disease_id,
                    'name' => $top->disease_name,
                ] : null,
                'match_score' => $top?->match_score,
                'assessed_at' => $assessment->created_at?->toIso8601String(),
            ]);
    }
}
