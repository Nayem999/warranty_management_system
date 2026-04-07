<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemorizeReport;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemorizeReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = MemorizeReport::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate($request->limit ?? 15);

        return $this->success($reports);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'filter' => 'nullable|array',
        ]);

        $report = MemorizeReport::create($data);

        return $this->created($report, 'Report created successfully.');
    }

    public function show(int $id): JsonResponse
    {
        $report = MemorizeReport::find($id);

        if (! $report) {
            return $this->notFound('Report not found.');
        }

        return $this->success($report);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = MemorizeReport::find($id);

        if (! $report) {
            return $this->notFound('Report not found.');
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:100',
            'filter' => 'nullable|array',
        ]);

        $report->update($data);

        return $this->success($report, 'Report updated successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $report = MemorizeReport::find($id);

        if (! $report) {
            return $this->notFound('Report not found.');
        }

        $report->delete();

        return $this->deleted('Report deleted successfully.');
    }
}
