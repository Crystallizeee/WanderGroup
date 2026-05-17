<?php

namespace App\Services;

/**
 * Optimized debt settlement using the "minimum number of transactions" algorithm.
 * Given net balances, calculates the minimum set of transfers to settle all debts.
 */
class DebtSettlementService
{
    /**
     * Calculate simplified settlement transactions.
     * Uses a greedy approach: match largest creditor with largest debtor.
     *
     * @param array $balances [user_id => net_balance] (positive = owed money, negative = owes money)
     * @return array of ['from' => user_id, 'to' => user_id, 'amount' => float]
     */
    public function calculateSettlements(array $balances): array
    {
        // Separate into creditors (positive balance) and debtors (negative balance)
        $creditors = [];
        $debtors = [];

        foreach ($balances as $userId => $net) {
            $net = round($net, 2);
            if ($net > 0.01) {
                $creditors[] = ['id' => $userId, 'amount' => $net];
            } elseif ($net < -0.01) {
                $debtors[] = ['id' => $userId, 'amount' => abs($net)];
            }
        }

        // Sort both by amount descending (greedy: settle largest first)
        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $settlements = [];
        $ci = 0;
        $di = 0;

        while ($ci < count($creditors) && $di < count($debtors)) {
            $transferAmount = min($creditors[$ci]['amount'], $debtors[$di]['amount']);
            $transferAmount = round($transferAmount, 2);

            if ($transferAmount > 0.01) {
                $settlements[] = [
                    'from' => $debtors[$di]['id'],
                    'to' => $creditors[$ci]['id'],
                    'amount' => $transferAmount,
                ];
            }

            $creditors[$ci]['amount'] -= $transferAmount;
            $debtors[$di]['amount'] -= $transferAmount;

            if ($creditors[$ci]['amount'] < 0.01) $ci++;
            if ($debtors[$di]['amount'] < 0.01) $di++;
        }

        return $settlements;
    }

    /**
     * Calculate net balances from trip expenses.
     *
     * @param \App\Models\Trip $trip
     * @return array [user_id => net_balance]
     */
    public function calculateNetBalances($trip): array
    {
        $balances = [];

        foreach ($trip->members as $member) {
            $balances[$member->id] = 0;
        }

        foreach ($trip->expenses as $expense) {
            // The payer paid the full amount
            if (isset($balances[$expense->paid_by])) {
                $balances[$expense->paid_by] += (float) $expense->amount;
            }

            // Each split reduces the person's balance (they owe this)
            foreach ($expense->splits as $split) {
                if (isset($balances[$split->user_id]) && !$split->is_settled) {
                    $balances[$split->user_id] -= (float) $split->amount;
                }
            }
        }

        return $balances;
    }
}
