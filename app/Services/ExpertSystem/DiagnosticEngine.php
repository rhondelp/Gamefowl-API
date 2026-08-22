<?php

namespace App\Services\ExpertSystem;

use App\Models\Disease;
use Illuminate\Support\Collection;

/**
 * Weighted symptom-matching inference engine for the GAMEFOWL expert system.
 *
 * SCORING FORMULA (implemented exactly, kept deterministic):
 *
 *   match_score(disease D, input symptoms S) =
 *       round( ( Σ weight(r) for r in rules(D) where r.symptom_id ∈ S )
 *              / ( Σ weight(r) for r in rules(D) )
 *              × 100 )
 *
 * - Only ACTIVE diseases are candidates.
 * - Rules pointing to an INACTIVE symptom are excluded from BOTH the
 *   numerator and the denominator (the rule is treated as if it does
 *   not exist).
 * - PHP round() is used: standard "half away from zero" rounding on a
 *   positive quantity — one documented rounding rule, no ambiguity.
 * - Diseases with no (effective) rules are excluded outright; so are
 *   diseases with zero overlap with the input. Neither can ever be a
 *   candidate regardless of the configured threshold.
 *
 * Division of responsibility: this service is DEFENSIVE only. It ignores
 * unknown, non-numeric, inactive, and duplicate symptom IDs. Validating
 * that submitted symptoms exist is the API layer's job (FormRequest in
 * the assessment milestone), not the engine's.
 */
class DiagnosticEngine
{
    /**
     * Severity ranking used to decide when vet_warning is surfaced.
     */
    private const SEVERITY_RANKS = [
        'mild' => 1,
        'moderate' => 2,
        'severe' => 3,
        'critical' => 4,
    ];

    /**
     * vet_warning is surfaced only when disease severity reaches this rank.
     */
    private const VET_WARNING_MIN_RANK = 3; // severe and above

    /**
     * Match the submitted symptoms against the knowledge base.
     *
     * @param  array<int, mixed>  $symptomIds
     * @return Collection<int, DiagnosisMatch> ranked best-first
     */
    public function diagnose(array $symptomIds): Collection
    {
        $selectedIds = collect($symptomIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        $lookup = array_flip($selectedIds->all());
        $threshold = (int) config('expertsystem.min_match_threshold', 20);
        $maxResults = max(1, (int) config('expertsystem.max_results', 5));

        $activeDiseases = Disease::query()
            ->where('is_active', true)
            ->with(['symptoms' => fn ($query) => $query->where('is_active', true)])
            ->get();

        $results = [];

        foreach ($activeDiseases as $disease) {
            // ->symptoms is already filtered to active symptoms, so each
            // entry below is one effective rule with its pivot weight.
            $rules = $disease->symptoms;

            $totalWeight = (int) $rules->sum(fn ($symptom) => $symptom->pivot->weight);

            if ($totalWeight <= 0) {
                continue; // no effective rules: not a candidate, avoids division by zero
            }

            $matched = $rules->filter(fn ($symptom) => isset($lookup[$symptom->id]));
            $missing = $rules->reject(fn ($symptom) => isset($lookup[$symptom->id]))->values();

            if ($matched->isEmpty()) {
                continue; // zero overlap: never a candidate
            }

            $matchedWeight = (int) $matched->sum(fn ($symptom) => $symptom->pivot->weight);
            $score = (int) round(($matchedWeight / $totalWeight) * 100);

            if ($score < $threshold) {
                continue;
            }

            $results[] = new DiagnosisMatch(
                diseaseId: $disease->id,
                diseaseName: $disease->name,
                matchScore: $score,
                matchedSymptoms: $matched
                    ->map(fn ($symptom) => ['id' => $symptom->id, 'name' => $symptom->name])
                    ->values()
                    ->all(),
                missingSymptoms: $missing
                    ->map(fn ($symptom) => ['id' => $symptom->id, 'name' => $symptom->name])
                    ->values()
                    ->all(),
                severity: $disease->severity,
                vetWarning: $this->shouldSurfaceVetWarning($disease->severity) ? $disease->vet_warning : null,
            );
        }

        usort($results, fn (DiagnosisMatch $a, DiagnosisMatch $b) => $b->matchScore <=> $a->matchScore
            ?: strcmp($a->diseaseName, $b->diseaseName));

        return collect(array_slice($results, 0, $maxResults));
    }

    private function shouldSurfaceVetWarning(string $severity): bool
    {
        return (self::SEVERITY_RANKS[$severity] ?? 0) >= self::VET_WARNING_MIN_RANK;
    }
}
