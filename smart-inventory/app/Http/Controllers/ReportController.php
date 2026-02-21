<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $service;

    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/reports/summary
     * Summary report — aggregated across branches based on user role.
     * Super admin sees all branches, managers see only their own.
     */
    public function summaryReport(Request $request)
    {
        $user = $request->user();
        $user->load('role');

        // Determine which branch IDs to include based on user role
        if ($user->role->name === 'super_admin') {
            $branchIds = Branch::pluck('id')->toArray();
        } else {
            $branchIds = Branch::where('manager_id', $user->id)->pluck('id')->toArray();
        }

        return response()->json(
            $this->service->getSummaryReport($branchIds)
        );
    }

    /**
     * GET /api/reports/branches/{branchId}
     * Get detailed report for a specific branch.
     * Managers can only view reports for branches they manage.
     */
    public function branchReport(Request $request, $branchId)
    {
        $user = $request->user();
        $user->load('role');

        // Authorization check — managers can only view reports for their own branches
        if ($user->role->name === 'branch_manager') {
            $ownsBranch = Branch::where('id', $branchId)
                ->where('manager_id', $user->id)
                ->exists();

            if (!$ownsBranch) {
                return response()->json([
                    'message' => 'Access denied. You can only view reports for your own branch.'
                ], 403);
            }
        }

        return response()->json(
            $this->service->getBranchReport($branchId)
        );
    }
}