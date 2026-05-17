<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Settlement;
use App\Models\ActivityLog;
use App\Services\DebtSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    public function __construct(
        private DebtSettlementService $debtService
    ) {}

    /**
     * Show the settlement dashboard with optimized transactions.
     */
    public function index(Trip $trip)
    {
        $trip->load(['expenses.splits', 'members', 'settlements.payer', 'settlements.payee']);

        $netBalances = $this->debtService->calculateNetBalances($trip);
        $suggestedSettlements = $this->debtService->calculateSettlements($netBalances);

        // Map user IDs to user objects for display
        $membersById = $trip->members->keyBy('id');
        $settlements = collect($suggestedSettlements)->map(function ($s) use ($membersById) {
            return [
                'from_user' => $membersById[$s['from']] ?? null,
                'to_user' => $membersById[$s['to']] ?? null,
                'amount' => $s['amount'],
            ];
        })->filter(fn($s) => $s['from_user'] && $s['to_user']);

        $completedSettlements = $trip->settlements()
            ->where('status', 'completed')
            ->with(['payer', 'payee'])
            ->latest()
            ->get();

        $totalDebt = collect($suggestedSettlements)->sum('amount');

        return view('trips.settlements', compact(
            'trip', 'settlements', 'completedSettlements', 'totalDebt', 'netBalances', 'membersById'
        ));
    }

    /**
     * Mark a settlement as completed.
     */
    public function settle(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'from_user' => ['required', 'exists:users,id'],
            'to_user' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $settlement = Settlement::create([
            'trip_id' => $trip->id,
            'from_user' => $validated['from_user'],
            'to_user' => $validated['to_user'],
            'amount' => $validated['amount'],
            'status' => 'completed',
            'payment_method' => $validated['payment_method'] ?? 'cash',
        ]);

        // Mark relevant expense splits as settled
        $this->markSplitsAsSettled($trip, $validated['from_user'], $validated['to_user'], $validated['amount']);

        ActivityLog::log($trip->id, Auth::id(), 'settled_debt', Settlement::class, $settlement->id, [
            'amount' => $validated['amount'],
        ]);

        return back()->with('success', 'Payment recorded!');
    }

    /**
     * Delete/cancel a settlement.
     */
    public function destroy(Trip $trip, Settlement $settlement)
    {
        // Revert relevant expense splits back to unsettled
        $this->revertSplitsAsSettled($trip, $settlement->from_user, $settlement->to_user, $settlement->amount);

        ActivityLog::log($trip->id, Auth::id(), 'deleted_settlement', Settlement::class, $settlement->id, [
            'amount' => $settlement->amount,
        ]);

        $settlement->delete();

        return back()->with('success', 'Settlement cancelled successfully!');
    }

    /**
     * Mark expense splits as settled up to the given amount.
     */
    private function markSplitsAsSettled(Trip $trip, int $fromUser, int $toUser, float $amount): void
    {
        // Find expenses where toUser paid, and fromUser has unsettled splits
        $expenses = $trip->expenses()
            ->where('paid_by', $toUser)
            ->with(['splits' => fn($q) => $q->where('user_id', $fromUser)->where('is_settled', false)])
            ->get();

        $remaining = $amount;
        foreach ($expenses as $expense) {
            foreach ($expense->splits as $split) {
                if ($remaining <= 0) break;
                $split->update(['is_settled' => true]);
                $remaining -= (float) $split->amount;
            }
        }
    }

    /**
     * Revert expense splits back to unsettled up to the given amount.
     */
    private function revertSplitsAsSettled(Trip $trip, int $fromUser, int $toUser, float $amount): void
    {
        // Find expenses where toUser paid, and fromUser has settled splits
        $expenses = $trip->expenses()
            ->where('paid_by', $toUser)
            ->with(['splits' => fn($q) => $q->where('user_id', $fromUser)->where('is_settled', true)])
            ->get();

        $remaining = $amount;
        foreach ($expenses as $expense) {
            foreach ($expense->splits as $split) {
                if ($remaining <= 0) break;
                $split->update(['is_settled' => false]);
                $remaining -= (float) $split->amount;
            }
        }
    }
}
