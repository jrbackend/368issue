<?php

namespace App\Http\Controllers;

use App\Models\TrafficVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrafficVisitController extends Controller
{
    /**
     * Show the log of allowed and blocked visits to the home page.
     */
    public function index(Request $request): Response
    {
        /** @var array{status?: string|null, search?: string|null} $filters */
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['allowed', 'blocked'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $status = $filters['status'] ?? null;
        $search = trim($filters['search'] ?? '');

        $visits = TrafficVisit::query()
            ->when($status !== null, fn (Builder $query) => $query->where('was_allowed', $status === 'allowed'))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('ip', 'like', "%{$search}%")
                    ->orWhere('city_name', 'like', "%{$search}%")
                    ->orWhere('region_name', 'like', "%{$search}%")
                    ->orWhere('country_code', strtoupper($search));
            }))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (TrafficVisit $visit) => [
                'id' => $visit->id,
                'ip' => $visit->ip,
                'country_code' => $visit->country_code,
                'region_name' => $visit->region_name,
                'city_name' => $visit->city_name,
                'device' => $visit->device,
                'is_bot' => $visit->is_bot,
                'operating_system' => $visit->operating_system,
                'browser' => $visit->browser,
                'language' => $visit->language,
                'user_agent' => $visit->user_agent,
                'was_allowed' => $visit->was_allowed,
                'blocked_reason' => $visit->blocked_reason,
                'created_at' => $visit->created_at->toIso8601String(),
            ]);

        $lastDay = TrafficVisit::query()->where('created_at', '>=', now()->subDay());
        $visitsLastDay = (clone $lastDay)->count();
        $allowedLastDay = (clone $lastDay)->where('was_allowed', true)->count();

        return Inertia::render('TrafficLogs', [
            'visits' => $visits,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'stats' => [
                'total' => $visitsLastDay,
                'allowed' => $allowedLastDay,
                'blocked' => $visitsLastDay - $allowedLastDay,
            ],
        ]);
    }
}
