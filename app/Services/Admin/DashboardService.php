<?php

namespace App\Services\Admin;

use App\Models\Disease;
use App\Models\Gamefowl;
use App\Models\HealthAssessment;
use App\Models\HealthAssessmentResult;
use App\Models\Symptom;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate statistics for the admin dashboard. Each stat is its own
 * small query method so it can be unit-tested and extended independently.
 */
class DashboardService
{
    /**
     * Definition note: a disease counts as "suggested" every time it
     * appears ANYWHERE in an assessment's ranked results — not only at
     * rank #1. This measures how often the engine surfaces each disease
     * overall; the rank-#1 view would answer a different question.
     */
    /**
     * Total registered accounts, INCLUDING deactivated (soft-deleted)
     * ones — so the headline always equals active + inactive from the
     * breakdown below.
     */
    public function totalUsers(): int
    {
        return User::withTrashed()->count();
    }

    public function usersByRole(): Collection
    {
        return User::withTrashed()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');
    }

    public function usersByActiveStatus(): Collection
    {
        $inactive = (int) User::onlyTrashed()->count();

        return collect([
            'active' => User::count(),
            'inactive' => $inactive,
        ]);
    }

    public function totalGamefowls(): int
    {
        // Active birds only: soft-deleted birds are excluded from this
        // headline count, consistent with owner-facing listings.
        return Gamefowl::count();
    }

    public function totalAssessments(): int
    {
        return HealthAssessment::count();
    }

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
                'top_possible_disease' => ($top = $assessment->results->first()) ? [
                    'id' => $top->disease_id,
                    'name' => $top->disease_name,
                ] : null,
                'match_score' => $top?->match_score,
                'assessed_at' => $assessment->created_at?->toIso8601String(),
            ]);
    }
}
