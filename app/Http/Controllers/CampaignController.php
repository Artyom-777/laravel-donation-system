<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'completed', 'upcoming'])],
            'sort' => ['nullable', Rule::in(['latest', 'goal-high', 'goal-low'])],
        ]);

        Campaign::query()
            ->whereIn('status', ['active', 'upcoming'])
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'completed']);

        $campaignQuery = Campaign::query()
            ->withSum([
                'donations as raised_amount' => fn (Builder $query) => $query->where('status', 'completed'),
            ], 'amount')
            ->withCount([
                'donations as donors_count' => fn (Builder $query) => $query
                    ->where('status', 'completed')
                    ->select(DB::raw('COUNT(DISTINCT donor_id)')),
            ]);

        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $campaignQuery->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $campaignQuery->where('status', $filters['status']);
        }

        match ($filters['sort'] ?? 'latest') {
            'goal-high' => $campaignQuery->orderByDesc('goal_amount'),
            'goal-low' => $campaignQuery->orderBy('goal_amount'),
            default => $campaignQuery->latest(),
        };

        $campaigns = $campaignQuery
            ->paginate(9)
            ->withQueryString();

        $campaignCounts = Campaign::query()
            ->select('status', DB::raw('COUNT(*) as campaign_count'))
            ->groupBy('status')
            ->pluck('campaign_count', 'status');

        return view('campaigns', [
            'campaigns' => $campaigns,
            'campaignCounts' => $campaignCounts,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'goal_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'category' => ['required', Rule::in(['education', 'health', 'food', 'environment', 'community', 'other'])],
            'status' => ['required', Rule::in(['active', 'upcoming'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $image = $request->file('image');
        unset($validated['image']);

        $imagePath = null;

        try {
            DB::transaction(function () use ($request, $validated, $image, &$imagePath): void {
                if ($image !== null) {
                    $imagePath = $image->store('campaigns', 'public');

                    if ($imagePath === false) {
                        throw new RuntimeException('The campaign image could not be stored.');
                    }

                    $validated['image_path'] = $imagePath;
                }

                $request->user()->campaigns()->create($validated);
            });
        } catch (Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()
            ->route('campaigns')
            ->with('status', 'Campaign created successfully.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->created_by === $request->user()->id, 403);

        if ($campaign->donations()->exists()) {
            return back()->withErrors([
                'campaign' => 'This campaign cannot be deleted because it has donation records.',
            ]);
        }

        $imagePath = $campaign->image_path;

        DB::transaction(function () use ($campaign): void {
            $campaign->delete();
        });

        if ($imagePath !== null && ! Storage::disk('public')->delete($imagePath)) {
            Log::error('A deleted campaign image could not be removed from storage.', [
                'campaign_id' => $campaign->id,
                'image_path' => $imagePath,
            ]);
        }

        return redirect()
            ->route('campaigns')
            ->with('status', 'Campaign deleted successfully.');
    }
}
