<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics retrieved successfully.',
            'data' => [
                'total_users' => $this->dashboard->totalUsers(),
                'users_by_role' => $this->dashboard->usersByRole(),
                'users_by_active_status' => $this->dashboard->usersByActiveStatus(),
                'total_gamefowls' => $this->dashboard->totalGamefowls(),
                'total_assessments' => $this->dashboard->totalAssessments(),
                'most_frequently_reported_symptoms' => $this->dashboard->topReportedSymptoms(),
                // "Suggested" = appeared anywhere in an assessment's ranked
                // results (any rank), per the documented definition.
                'most_frequently_suggested_diseases' => $this->dashboard->topSuggestedDiseases(),
                'recent_assessments' => $this->dashboard->recentAssessments(),
            ],
        ]);
    }
}
