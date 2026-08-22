<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRuleRequest;
use App\Http\Requests\Admin\UpdateRuleRequest;
use App\Models\DiseaseSymptomRule;
use Illuminate\Http\JsonResponse;

class RuleController extends Controller
{
    public function store(StoreRuleRequest $request): JsonResponse
    {
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
                    'weight' => $rule->weight,
                ],
            ],
        ]);
    }

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
