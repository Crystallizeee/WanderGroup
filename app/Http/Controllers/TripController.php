<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TripController extends Controller
{
    /**
     * Display user dashboard with active trips.
     */
    public function dashboard()
    {
        $user = Auth::user();

        $activeTrips = $user->trips()
            ->active()
            ->with(['members', 'creator'])
            ->withCount('members')
            ->orderByDesc('start_date')
            ->get();

        $completedTrips = $user->trips()
            ->completed()
            ->with('members')
            ->latest()
            ->limit(5)
            ->get();

        $recentActivity = ActivityLog::whereIn('trip_id', $user->trips()->pluck('trips.id'))
            ->with(['user', 'trip'])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard', compact('activeTrips', 'completedTrips', 'recentActivity'));
    }

    /**
     * Show create trip form.
     */
    public function create()
    {
        return view('trips.create');
    }

    /**
     * Store a new trip.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'cover_image' => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'planning';

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('trips/covers', 'public');
        } else {
            // Auto-fetch cover image from Unsplash based on destination
            try {
                $unsplashKey = env('UNSPLASH_ACCESS_KEY');
                if ($unsplashKey) {
                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->get('https://api.unsplash.com/search/photos', [
                            'query' => $validated['destination'] . ' travel landscape',
                            'per_page' => 1,
                            'orientation' => 'landscape',
                            'client_id' => $unsplashKey,
                        ]);
                    if ($response->successful() && !empty($response->json('results'))) {
                        $validated['cover_image'] = $response->json('results.0.urls.regular');
                    }
                }
            } catch (\Exception $e) {
                // Silently fail — trip will use gradient fallback
            }
        }

        $trip = Trip::create($validated);

        // Creator becomes organizer
        $trip->members()->attach(Auth::id(), ['role' => 'organizer']);

        // Generate itinerary days
        $startDate = $trip->start_date;
        $endDate = $trip->end_date;
        $dayNumber = 1;
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $trip->itineraryDays()->create([
                'date' => $date->toDateString(),
                'day_number' => $dayNumber++,
            ]);
        }

        // Log activity
        ActivityLog::log($trip->id, Auth::id(), 'created_trip', Trip::class, $trip->id, [
            'title' => $trip->title,
            'destination' => $trip->destination,
        ]);

        return redirect()->route('trips.show', $trip)
            ->with('success', 'Trip created successfully! Start planning your adventure.');
    }

    /**
     * Show trip overview/dashboard.
     */
    public function show(Trip $trip)
    {
        $trip->load([
            'members',
            'itineraryDays.items',
            'expenses.payer',
            'polls.options.votes',
            'activityLogs' => fn($q) => $q->with('user')->latest()->limit(10),
        ]);

        $totalSpent = $trip->expenses()->sum('amount');
        $memberBalances = $this->calculateBalances($trip);

        // Get AI recommendation for the dashboard
        $aiService = app(\App\Services\TripAIService::class);
        $aiRecommendation = $aiService->getSmartRecommendation($trip);

        return view('trips.show', compact('trip', 'totalSpent', 'memberBalances', 'aiRecommendation'));
    }

    /**
     * Show itinerary for a trip.
     */
    public function itinerary(Trip $trip)
    {
        $trip->load(['itineraryDays.items.creator', 'members']);

        $currentDay = request('day', 1);
        $selectedDay = $trip->itineraryDays->firstWhere('day_number', $currentDay)
            ?? $trip->itineraryDays->first();

        return view('trips.itinerary', compact('trip', 'selectedDay', 'currentDay'));
    }

    /**
     * Show finances for a trip.
     */
    public function finances(Trip $trip)
    {
        $trip->load(['expenses.payer', 'expenses.splits', 'members', 'settlements']);

        $totalSpent = $trip->expenses()->sum('amount');
        $memberBalances = $this->calculateBalances($trip);
        $recentExpenses = $trip->expenses()->with('payer')->latest()->limit(10)->get();

        $categoryBreakdown = $trip->expenses()
            ->reorder()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return view('trips.finances', compact(
            'trip', 'totalSpent', 'memberBalances', 'recentExpenses', 'categoryBreakdown'
        ));
    }

    /**
     * Show voting for a trip.
     */
    public function voting(Trip $trip)
    {
        $trip->load(['polls.options.votes.user', 'polls.creator', 'members']);

        $activePolls = $trip->polls()->where('status', 'active')->with('options.votes')->get();
        $closedPolls = $trip->polls()->where('status', 'closed')->with('options.votes')->get();

        return view('trips.voting', compact('trip', 'activePolls', 'closedPolls'));
    }

    /**
     * Show checklist for a trip.
     */
    public function checklist(Trip $trip)
    {
        $trip->load(['checklistItems.assignee', 'checklistItems.creator', 'members']);

        $items = $trip->checklistItems()
            ->with(['assignee', 'creator'])
            ->orderBy('category')
            ->orderBy('is_checked')
            ->get();

        // Split into Shared vs Personal
        $sharedItems = $items->where('is_shared', true);
        $personalItems = $items->where('is_shared', false);

        // Group shared items by category
        $groupedSharedItems = $sharedItems->groupBy('category');

        // Group personal items by assignee (member)
        $personalByMember = $personalItems->groupBy('assigned_to');

        // Duplicate Detection Logic (Only for Shared Items)
        $titles = [];
        foreach ($sharedItems as $item) {
            $normalizedTitle = strtolower(trim($item->title));
            if (!isset($titles[$normalizedTitle])) {
                $titles[$normalizedTitle] = [];
            }
            $titles[$normalizedTitle][] = $item->id;
        }

        $duplicateIds = [];
        foreach ($titles as $ids) {
            if (count($ids) > 1) {
                $duplicateIds = array_merge($duplicateIds, $ids);
            }
        }

        // AI Recommendations (triggered by session or request)
        $aiSuggestions = [];
        if (request('suggest') == 1) {
            $aiService = new \App\Services\TripAIService();
            $startDate = $trip->start_date ? $trip->start_date->format('F Y') : 'waktu dekat';
            $aiSuggestions = $aiService->getPackingRecommendations(
                $trip->destination, 
                $startDate, 
                $items->pluck('title')->toArray()
            );
        }

        return view('trips.checklist', compact(
            'trip', 'groupedSharedItems', 'personalByMember', 'duplicateIds', 'aiSuggestions'
        ));
    }

    /**
     * Calculate debt balances and generate optimized settlement transactions.
     */
    private function calculateBalances(Trip $trip): array
    {
        $netBalances = [];
        $members = $trip->members;

        foreach ($members as $member) {
            $netBalances[$member->id] = 0;
        }

        // Calculate net balance for each member
        foreach ($trip->expenses as $expense) {
            $netBalances[$expense->paid_by] += (float) $expense->amount;
            
            foreach ($expense->splits as $split) {
                if (!$split->is_settled) {
                    $netBalances[$split->user_id] -= (float) $split->amount;
                }
            }
        }

        // Split into debtors and creditors
        $debtors = [];
        $creditors = [];
        foreach ($netBalances as $userId => $amount) {
            if ($amount < -0.01) {
                $debtors[] = ['id' => $userId, 'amount' => abs($amount)];
            } elseif ($amount > 0.01) {
                $creditors[] = ['id' => $userId, 'amount' => $amount];
            }
        }

        // Greedy Debt Minimization Algorithm
        $transactions = [];
        $i = 0; $j = 0;
        while ($i < count($debtors) && $j < count($creditors)) {
            $settleAmount = min($debtors[$i]['amount'], $creditors[$j]['amount']);
            
            $transactions[] = [
                'from' => $members->find($debtors[$i]['id']),
                'to' => $members->find($creditors[$j]['id']),
                'amount' => $settleAmount,
            ];

            $debtors[$i]['amount'] -= $settleAmount;
            $creditors[$j]['amount'] -= $settleAmount;

            if ($debtors[$i]['amount'] < 0.01) $i++;
            if ($creditors[$j]['amount'] < 0.01) $j++;
        }

        return [
            'net' => $netBalances,
            'optimized_transactions' => $transactions,
        ];
    }

    /**
     * Show trip edit form.
     */
    public function edit(Trip $trip)
    {
        return view('trips.edit', compact('trip'));
    }

    /**
     * Update trip details.
     */
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'status' => ['nullable', Rule::in(['planning', 'active', 'completed', 'cancelled'])],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('trips/covers', 'public');
        }

        DB::transaction(function () use ($trip, $validated) {
            $trip->update($validated);

            // Sync Itinerary Days
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);

            $newDates = [];
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $newDates[] = $date->toDateString();
            }

            $existingDays = $trip->itineraryDays()->orderBy('day_number')->get();
            
            // Move existing days to temporary unique far-future dates to avoid PostgreSQL immediate unique constraint violations
            foreach ($existingDays as $index => $existingDay) {
                $tempDate = \Carbon\Carbon::create(3000, 1, 1)->addDays($trip->id * 100 + $index)->toDateString();
                $existingDay->update(['date' => $tempDate]);
            }

            $newDaysCount = count($newDates);
            $existingDaysCount = $existingDays->count();

            for ($i = 0; $i < $newDaysCount; $i++) {
                $dateStr = $newDates[$i];
                $dayNum = $i + 1;

                if ($i < $existingDaysCount) {
                    $existingDays[$i]->update([
                        'date' => $dateStr,
                        'day_number' => $dayNum,
                    ]);
                } else {
                    $trip->itineraryDays()->create([
                        'date' => $dateStr,
                        'day_number' => $dayNum,
                    ]);
                }
            }

            if ($existingDaysCount > $newDaysCount) {
                for ($i = $newDaysCount; $i < $existingDaysCount; $i++) {
                    $existingDays[$i]->items()->delete();
                    $existingDays[$i]->delete();
                }
            }
        });

        ActivityLog::log($trip->id, Auth::id(), 'updated_trip', Trip::class, $trip->id, [
            'title' => $trip->title,
        ]);

        return redirect()->route('trips.show', $trip)
            ->with('success', 'Trip berhasil diperbarui!');
    }

    /**
     * Delete a trip (soft delete).
     */
    public function destroy(Trip $trip)
    {
        $title = $trip->title;
        $trip->delete();

        return redirect()->route('dashboard')
            ->with('success', "Trip '{$title}' berhasil dihapus.");
    }

    /**
     * Show activity log / notifications center.
     */
    public function activities(Trip $trip)
    {
        $activities = \App\Models\ActivityLog::where('trip_id', $trip->id)
            ->with('user')
            ->latest()
            ->paginate(50);
            
        return view('trips.activities', compact('trip', 'activities'));
    }

    /**
     * Show members management for a trip.
     */
    public function members(Trip $trip)
    {
        $trip->load(['members', 'invitations']);
        return view('trips.members', compact('trip'));
    }

    /**
     * Update a member's role (promote/demote).
     */
    public function updateMemberRole(Request $request, Trip $trip, \App\Models\User $user)
    {
        $request->validate([
            'role' => ['required', 'in:organizer,member'],
        ]);

        // Cannot change the trip creator's role
        if ($user->id === $trip->created_by) {
            return back()->with('error', 'Cannot change the trip creator\'s role.');
        }

        $trip->members()->updateExistingPivot($user->id, ['role' => $request->role]);

        ActivityLog::log($trip->id, Auth::id(), 'changed_role', \App\Models\User::class, $user->id, [
            'title' => $user->name,
            'role' => $request->role,
        ]);

        return back()->with('success', "{$user->name} is now {$request->role}.");
    }

    /**
     * Remove a member from the trip.
     */
    public function removeMember(Trip $trip, \App\Models\User $user)
    {
        // Cannot remove yourself or the creator
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot remove yourself from the trip.');
        }
        if ($user->id === $trip->created_by) {
            return back()->with('error', 'Cannot remove the trip creator.');
        }

        $trip->members()->detach($user->id);

        ActivityLog::log($trip->id, Auth::id(), 'removed_member', \App\Models\User::class, $user->id, [
            'title' => $user->name,
        ]);

        return back()->with('success', "{$user->name} has been removed from the trip.");
    }

    /**
     * Show budget management dashboard for a trip.
     */
    public function budget(Trip $trip)
    {
        $trip->load(['itineraryDays.items', 'expenses']);

        $totalBudget = (float) ($trip->budget ?? 0);

        // Get custom allocated category budgets
        $categoryBudgets = $trip->category_budgets ?? [
            'lodging' => 0,
            'food' => 0,
            'transport' => 0,
            'activities' => 0,
            'misc' => 0
        ];

        // Total allocated is sum of category budgets
        $totalAllocated = (float) array_sum($categoryBudgets);
        $remainingBudget = $totalBudget - $totalAllocated;
        
        $utilizationPercentage = $totalBudget > 0 
            ? min(round(($totalAllocated / $totalBudget) * 100), 100) 
            : 0;

        // Sum actual spent by category from expenses table
        $actualSpentQuery = $trip->expenses()
            ->reorder()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $actualSpent = [
            'lodging' => (float) ($actualSpentQuery['accommodation'] ?? 0),
            'food' => (float) ($actualSpentQuery['food'] ?? 0),
            'transport' => (float) ($actualSpentQuery['transport'] ?? 0),
            'activities' => (float) ($actualSpentQuery['activity'] ?? 0),
            'misc' => (float) (($actualSpentQuery['shopping'] ?? 0) + ($actualSpentQuery['other'] ?? 0))
        ];

        // Fetch detailed itinerary items with estimated cost
        $detailedItems = \App\Models\ItineraryItem::whereIn(
            'itinerary_day_id',
            $trip->itineraryDays()->pluck('id')
        )->orderBy('sort_order')->get();

        $memberCount = max($trip->members->count(), 1);

        $memberShares = \App\Models\ExpenseSplit::join('expenses', 'expense_splits.expense_id', '=', 'expenses.id')
            ->where('expenses.trip_id', $trip->id)
            ->whereNull('expenses.deleted_at')
            ->selectRaw('expense_splits.user_id, SUM(expense_splits.amount) as total')
            ->groupBy('expense_splits.user_id')
            ->pluck('total', 'user_id');

        return view('trips.budget', compact(
            'trip',
            'totalBudget',
            'categoryBudgets',
            'totalAllocated',
            'remainingBudget',
            'utilizationPercentage',
            'actualSpent',
            'detailedItems',
            'memberCount',
            'memberShares'
        ));
    }

    /**
     * Update category budget allocations.
     */
    public function updateCategoryBudgets(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'lodging' => ['required', 'numeric', 'min:0'],
            'food' => ['required', 'numeric', 'min:0'],
            'transport' => ['required', 'numeric', 'min:0'],
            'activities' => ['required', 'numeric', 'min:0'],
            'misc' => ['required', 'numeric', 'min:0'],
        ]);

        $trip->update([
            'category_budgets' => $validated
        ]);

        return back()->with('success', 'Kategori budget berhasil diperbarui!');
    }
}
