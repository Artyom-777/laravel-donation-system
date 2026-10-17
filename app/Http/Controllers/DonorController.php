<?php

namespace App\Http\Controllers;

use App\Models\Donor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonorController extends Controller
{
    public function index(): View
    {
        $donors = Donor::query()
            ->withSum('donations as total_donations', 'amount')
            ->withCount('donations')
            ->with('latestDonation.campaign')
            ->latest()
            ->get();

        return view('donors', [
            'donors' => $donors,
            'donorCount' => $donors->count(),
            'donationTotal' => $donors->sum('total_donations'),
            'repeatDonorCount' => $donors->where('donations_count', '>=', 2)->count(),
            'newDonorCount' => $donors->where('created_at', '>=', now()->subDays(30))->count(),
            'countries' => $donors->pluck('country')->filter()->unique()->sort()->values(),
            'campaigns' => $donors->pluck('latestDonation.campaign.title')->filter()->unique()->sort()->values(),
        ]);
    }

    public function create(): View
    {
        return view('donors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('donors', 'email')],
            'phone' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        Donor::query()->create($validated);

        return redirect()
            ->route('donors')
            ->with('status', 'Donor saved successfully.');
    }
}
