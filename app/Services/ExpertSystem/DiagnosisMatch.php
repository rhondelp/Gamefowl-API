<?php

namespace App\Services\ExpertSystem;

/**
 * Immutable result of matching one candidate disease against the
 * submitted symptoms. Carries plain scalars/arrays only — never an
 * Eloquent model — so it can be cached, serialized, or tested freely.
 *
 * @property array<int, array{id: int, name: string}> $matchedSymptoms
 * @property array<int, array{id: int, name: string}> $missingSymptoms
 */
class DiagnosisMatch
{
    /**
     * @param array<int, array{id: int, name: string}> $matchedSymptoms
     * @param array<int, array{id: int, name: string}> $missingSymptoms
     */
    public function __construct(
        public readonly int $diseaseId,
        public readonly string $diseaseName,
        public readonly int $matchScore,
        public readonly array $matchedSymptoms,
        public readonly array $missingSymptoms,
        public readonly string $severity,
        public readonly ?string $vetWarning,
    ) {
    }

    /**
     * API-facing representation (used by the assessment endpoint in a
     * later milestone).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'disease' => [
                'id' => $this->diseaseId,
                'name' => $this->diseaseName,
                'severity' => $this->severity,
                'vet_warning' => $this->vetWarning,
            ],
            'match_score' => $this->matchScore,
            'matched_symptoms' => $this->matchedSymptoms,
            'missing_symptoms' => $this->missingSymptoms,
        ];
    }
}
