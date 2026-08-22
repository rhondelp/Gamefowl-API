<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRuleRequest;
use App\Http\Requests\Admin\UpdateRuleRequest;
use App\Models\DiseaseSymptomRule;
use Illuminate\Http\JsonResponse;

/**
 * File: app/Http/Controllers/Admin/RuleController.php
 *
 * Purpose:
 *   Manage the knowledge base's core data — the weighted rules that connect
 *   symptoms to diseases:
 *     POST   /api/v1/admin/rules       — attach a (disease, symptom, weight) pair
 *     PUT    /api/v1/admin/rules/{id}  — change a rule's weight
 *     DELETE /api/v1/admin/rules/{id}  — remove the rule entirely
 *
 * How it fits into the project:
 *   DiagnosticEngine reads these rows to score assessments, so every change
 *   here directly changes what the expert system suggests next time. Rules
 *   are the ONE exception to the "deactivate, don't delete" convention:
 *   removing a rule is safe because rules are engine configuration, not
 *   historical records — past assessments stored their own snapshots.
 *
 * Note: rules are addressed by their pivot-table id ({id}), resolved
 * manually rather than through route-model binding (Pivot subclasses and
 * implicit binding don't mix well).
 */
class RuleController extends Controller
{
    /**
     * Attach a symptom to a disease with an importance weight.
     *
     * StoreRuleRequest guarantees: both IDs exist, the weight is an integer
     * inside 1–5 (constants on DiseaseSymptomRule), and the same
     * disease+symptom pair isn't already linked (DB unique constraint backs
     * this up as a second layer).
     */
    public function store(StoreRuleRequest $request): JsonResponse
    {
        // only() copies exactly the three whitelisted fields into the insert.
        $rule = DiseaseSymptomRule::create($request->only(['disease_id', 'symptom_id', 'weight']));

        return response()->json([
            'success' => true,
            'message' => 'Rule created successfully.',
            'data' => [
                'rule' => [
                    'id' => $rule->id,
                    'disease_id' => $rule->disease_id,
                    'symptom_id' => $rule->symptom_id,
                    'weight' => $rule->weight,
                ],
            ],
        ], 201);
    }

    /**
     * Change a rule's weight (the "how strongly does this symptom point at
     * this disease" dial). Only the weight is editable; moving a rule to a
     * different pair means deleting and creating one.
     */
    public function update(UpdateRuleRequest $request, int $id): JsonResponse
    {
        $rule = DiseaseSymptomRule::findOrFail($id);
        $rule->update(['weight' => $request->validated('weight')]);

        return response()->json([
            'success' => true,
            'message' => 'Rule updated successfully.',
            'data' => [
                'rule' => [
                    'id' => $rule->id,
                    'disease_id' => $rule->disease_id,
                    'symptom_id' => $rule->symptom_id,
                    // Casted to int by the model's casts().
                    'weight' => $rule->weight,
                ],
            ],
        ]);
    }

    /**
     * Remove a rule from the knowledge base. Future assessments stop seeing
     * this connection immediately; existing assessment results keep their
     * stored snapshots, so history is unaffected.
     */
    public function destroy(int $id): JsonResponse
    {
        $rule = DiseaseSymptomRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rule removed successfully.',
        ]);
    }
}
