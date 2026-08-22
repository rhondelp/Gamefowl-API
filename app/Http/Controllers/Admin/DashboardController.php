<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * File: app/Http/Controllers\Admin/DashboardController.php
 *
 * Purpose:
 *   Single endpoint backing the admin dashboard:
 *     GET /api/v1/admin/dashboard
 *
 *   Returns system-wide aggregates: user totals with role/status breakdowns,
 *   bird and assessment counts, most-reported symptoms, most-suggested
 *   diseases, and the latest assessments across all owners.
 *
 * How it fits into the project:
 *   All query logic lives in DashboardService — this controller only wires
 *   the service into the standard success envelope. Keeping each stat in its
 *   own service method makes every number independently unit-testable and
 *   easy to extend later.
 */
class DashboardController extends Controller
{
    /**
     * Laravel injects the DashboardService singleton through this
     * constructor; tests can substitute a mock if ever needed.
     */
    public function __construct(private DashboardService $dashboard)
    {
    }

    /**
     * Assemble every dashboard stat into one response.
     *
     * Note on definitions (also documented in DashboardService):
     * - "most_frequently_suggested_diseases" counts a disease each time it
     *   appears ANYWHERE in an assessment's ranked results, not just #1.
     * - recent_assessments is capped at 10 newest, summarized for listing.
     */
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
                // Definition reminder for anyone reading raw responses.
                'most_frequently_suggested_diseases' => $this->dashboard->topSuggestedDiseases(),
                'recent_assessments' => $this->dashboard->recentAssessments(),
            ],
        ]);
    }
}
