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
     * Match the submitted symptoms against the knowledge base and return
     * ranked disease matches.
     *
     * Called by HealthAssessmentController::store during every assessment
     * submission. Reads config for threshold/limits at call time so tests
     * can override behavior without rebuilding the service.
     *
     * Steps:
     *  1. Sanitize input: keep numeric IDs only, cast to int, remove
     *     duplicates ("Bloody droppings ticked twice" must not double-count).
     *  2. Load all active diseases WITH their active-symptom rules in a
     *     single eager load (the where() inside the closure is what filters
     *     out inactive symptoms — those rules vanish from both sums).
     *  3. Score each disease with the formula in the class docblock.
     *  4. Drop scores below the configured threshold; sort by score desc,
     *     then name asc; cut to max_results.
     *
     * @param  array<int, mixed>  $symptomIds  raw symptom IDs from the HTTP
     *         request; may contain junk (strings/nulls/duplicates) which is
     *         silently ignored.
     * @return Collection<int, DiagnosisMatch> ranked best-first; empty when
     *         the input is empty or nothing matches strongly enough.
     */
    public function diagnose(array $symptomIds): Collection
    {
        // Step 1: defensive input normalization (see docblock above).
        $selectedIds = collect($symptomIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        // array_flip builds an id => position map so the per-rule membership
        // test below is an O(1) isset() instead of an O(n) contains().
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

            // Split this disease's rules into "reported by the owner" vs
            // "not reported". The missing half powers the transparency
            // feature ("score would be higher if the bird also showed X").
            $matched = $rules->filter(fn ($symptom) => isset($lookup[$symptom->id]));
            $missing = $rules->reject(fn ($symptom) => isset($lookup[$symptom->id]))->values();

            if ($matched->isEmpty()) {
                continue; // zero overlap: never a candidate
            }

            // THE FORMULA: matched weight / total weight, scaled to 0–100
            // and rounded once (half away from zero) for determinism.
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

        // Final ranking: score DESC, then name ASC so equal scores always
        // appear in a predictable order (pinned by unit tests).
        usort($results, fn (DiagnosisMatch $a, DiagnosisMatch $b) => $b->matchScore <=> $a->matchScore
            ?: strcmp($a->diseaseName, $b->diseaseName));

        return collect(array_slice($results, 0, $maxResults));
    }

    /**
     * Decide whether a disease's stored vet_warning should be included in
     * its result. Only severe (rank 3) and critical (rank 4) conditions
     * surface the warning; mild/moderate ones hide it even if text exists.
     *
     * @return bool true when the severity rank meets VET_WARNING_MIN_RANK.
     */
    private function shouldSurfaceVetWarning(string $severity): bool
    {
        // Unknown severities rank 0, which never meets the minimum —
        // a safe default if bad data ever sneaks in.
        return (self::SEVERITY_RANKS[$severity] ?? 0) >= self::VET_WARNING_MIN_RANK;
    }
}
