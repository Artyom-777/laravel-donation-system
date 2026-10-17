<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $now = now();
        $yearStart = $now->copy()->startOfYear();
        $nextYearStart = $yearStart->copy()->addYear();

        Campaign::query()
            ->whereIn('status', ['active', 'upcoming'])
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'completed']);

        $summary = Donation::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_amount")
            ->selectRaw("COUNT(DISTINCT CASE WHEN status = 'completed' THEN donor_id END) as total_donors")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_donations")
            ->firstOrFail();

        $monthlyQuery = Donation::query()
            ->where('status', 'completed')
            ->where('donated_at', '>=', $yearStart)
            ->where('donated_at', '<', $nextYearStart);

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = $yearStart->copy()->addMonths($month - 1);
            $nextMonthStart = $monthStart->copy()->addMonth();
            $monthlyQuery->selectRaw(
                "COALESCE(SUM(CASE WHEN donated_at >= ? AND donated_at < ? THEN amount ELSE 0 END), 0) as month_{$month}",
                [$monthStart, $nextMonthStart],
            );
        }

        $monthlyTotals = $monthlyQuery->firstOrFail();
        $monthlyDonationAmounts = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyDonationAmounts[] = (float) $monthlyTotals->{"month_{$month}"};
        }

        $chartMaximum = max(500, (int) (ceil(max($monthlyDonationAmounts) / 500) * 500));
        $monthlyDonationPoints = [];
        $monthlyDonationLabels = [];
        $monthlyDonationGridCoordinates = [];
        $monthlyDonationChartPoints = [];

        foreach ($monthlyDonationAmounts as $index => $amount) {
            $x = 48 + ($index * 540 / 11);
            $y = 170 - ($amount / $chartMaximum * 144);
            $x = round($x, 2);
            $y = round($y, 2);
            $monthlyDonationPoints[] = $x.','.$y;
            $monthlyDonationGridCoordinates[] = $x;
            $monthlyDonationChartPoints[] = ['x' => $x, 'y' => $y];
            $monthlyDonationLabels[] = $yearStart->copy()->addMonths($index)->format('M');
        }

        $monthlyDonationSummary = [];

        foreach ($monthlyDonationLabels as $index => $monthLabel) {
            $monthlyDonationSummary[] = $monthLabel.': $'.number_format($monthlyDonationAmounts[$index], 2);
        }

        $chartAxisLabels = [];

        for ($step = 4; $step >= 0; $step--) {
            $chartAxisLabels[] = number_format($chartMaximum * $step / 4);
        }

        $campaignTotals = Campaign::query()
            ->whereHas('donations', fn (Builder $query): Builder => $query->where('status', 'completed'))
            ->withSum([
                'donations as raised_amount' => fn (Builder $query): Builder => $query->where('status', 'completed'),
            ], 'amount')
            ->orderByDesc('raised_amount')
            ->limit(5)
            ->get(['id', 'title']);

        $totalRaised = (float) $summary->total_amount;
        $topCampaignsRaised = 0.0;
        $campaignBreakdown = [];
        $campaignColors = ['#3784f6', '#44c18a', '#ffa34f', '#e94f6a', '#8259dd', '#9aa7b9'];

        foreach ($campaignTotals as $index => $campaign) {
            $amount = (float) $campaign->raised_amount;
            $topCampaignsRaised += $amount;
            $campaignBreakdown[] = [
                'title' => $campaign->title,
                'amount' => $amount,
                'percentage' => $totalRaised > 0 ? (int) round($amount / $totalRaised * 100) : 0,
                'color' => $campaignColors[$index],
            ];
        }

        $otherCampaignsRaised = max(0, $totalRaised - $topCampaignsRaised);

        if ($otherCampaignsRaised > 0) {
            $campaignBreakdown[] = [
                'title' => 'Other campaigns',
                'amount' => $otherCampaignsRaised,
                'percentage' => (int) round($otherCampaignsRaised / $totalRaised * 100),
                'color' => $campaignColors[5],
            ];
        }

        $campaignGradientStops = [];
        $runningPercentage = 0.0;

        foreach ($campaignBreakdown as $item) {
            $nextPercentage = $runningPercentage + ($totalRaised > 0 ? $item['amount'] / $totalRaised * 100 : 0);
            $campaignGradientStops[] = "{$item['color']} {$runningPercentage}% {$nextPercentage}%";
            $runningPercentage = $nextPercentage;
        }

        $campaignChartGradient = $campaignGradientStops === []
            ? 'conic-gradient(#e7edf5 0 100%)'
            : 'conic-gradient('.implode(', ', $campaignGradientStops).')';

        $activeCampaigns = Campaign::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->withSum([
                'donations as raised_amount' => fn (Builder $query): Builder => $query->where('status', 'completed'),
            ], 'amount')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'goal_amount', 'status'])
            ->map(function (Campaign $campaign, int $index): Campaign {
                $goalAmount = (float) $campaign->goal_amount;
                $raisedAmount = (float) ($campaign->raised_amount ?? 0);
                $campaign->setAttribute(
                    'progress_percentage',
                    $goalAmount > 0 ? min(100, (int) round($raisedAmount / $goalAmount * 100)) : 0,
                );
                $campaign->setAttribute('progress_color', ['blue', 'green', 'orange', 'red', 'purple'][$index % 5]);

                return $campaign;
            });

        $recentDonations = Donation::query()
            ->with(['donor:id,name', 'campaign:id,title'])
            ->orderByDesc('donated_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'stats' => [
                'totalAmount' => $totalRaised,
                'totalDonors' => (int) $summary->total_donors,
                'activeCampaigns' => Campaign::query()
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', today())
                    ->whereDate('end_date', '>=', today())
                    ->count(),
                'totalUsers' => User::query()->count(),
                'pendingDonations' => (int) $summary->pending_donations,
            ],
            'year' => $now->year,
            'monthlyDonationLabels' => $monthlyDonationLabels,
            'monthlyDonationSummary' => $monthlyDonationSummary,
            'monthlyDonationGridCoordinates' => $monthlyDonationGridCoordinates,
            'monthlyDonationChartPoints' => $monthlyDonationChartPoints,
            'monthlyDonationPoints' => implode(' ', $monthlyDonationPoints),
            'chartAxisLabels' => $chartAxisLabels,
            'campaignBreakdown' => $campaignBreakdown,
            'campaignChartGradient' => $campaignChartGradient,
            'activeCampaigns' => $activeCampaigns,
            'recentDonations' => $recentDonations,
        ]);
    }
}
