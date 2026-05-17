<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * Store a new expense with automatic split calculation.
     */
    public function store(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'category' => ['required', 'string', 'in:accommodation,food,transport,activity,shopping,other'],
            'split_method' => ['required', 'string', 'in:equal,exact,percentage,shares,itemized'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt_image' => ['nullable', 'image', 'max:10240'], // 10MB for receipts
            'splits' => ['required_unless:split_method,equal', 'array'],
            'splits.*.user_id' => ['required_with:splits', 'exists:users,id'],
            'splits.*.amount' => ['required_with:splits', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        return DB::transaction(function () use ($validated, $trip, $request) {
            $validated['trip_id'] = $trip->id;
            $validated['paid_by'] = Auth::id();
            $validated['currency'] = $trip->currency;

            if ($request->hasFile('receipt_image')) {
                $validated['receipt_image'] = $request->file('receipt_image')
                    ->store('trips/' . $trip->id . '/receipts', 'public');
            }

            // Map itemized UI method to exact DB method to bypass SQLite CHECK constraint
            if ($validated['split_method'] === 'itemized') {
                $validated['split_method'] = 'exact';
            }

            $expense = Expense::create($validated);
            
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    $expense->items()->create([
                        'name' => $itemData['name'],
                        'quantity' => $itemData['quantity'] ?? 1,
                        'unit_price' => $itemData['unit_price'] ?? $itemData['price'],
                        'price' => $itemData['price'],
                        'assigned_to' => $itemData['assigned_to'] ?? null,
                    ]);
                }
            }

            // Calculate splits based on method
            $members = $trip->members;
            
            if ($validated['split_method'] === 'equal') {
                $splitAmount = round($validated['amount'] / $members->count(), 2);
                foreach ($members as $member) {
                    ExpenseSplit::create([
                        'expense_id' => $expense->id,
                        'user_id' => $member->id,
                        'amount' => $splitAmount,
                    ]);
                }
            } elseif ($validated['split_method'] === 'percentage') {
                foreach ($validated['splits'] as $split) {
                    ExpenseSplit::create([
                        'expense_id' => $expense->id,
                        'user_id' => $split['user_id'],
                        'amount' => ($split['amount'] / 100) * $validated['amount'],
                    ]);
                }
            } else {
                // Exact amounts
                foreach ($validated['splits'] as $split) {
                    ExpenseSplit::create([
                        'expense_id' => $expense->id,
                        'user_id' => $split['user_id'],
                        'amount' => $split['amount'],
                    ]);
                }
            }

            // Log activity
            ActivityLog::log($trip->id, Auth::id(), 'added_expense', Expense::class, $expense->id, [
                'title' => $expense->title,
                'amount' => $expense->amount,
            ]);

            return redirect()->route('trips.finances', $trip)
                ->with('success', 'Expense added successfully.');
        });
    }

    /**
     * Update an existing expense (basic fields only, splits stay unchanged).
     */
    public function update(Request $request, Trip $trip, Expense $expense)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'category' => ['required', 'string', 'in:accommodation,food,transport,activity,shopping,other'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldAmount = $expense->amount;
        $expense->update($validated);

        // Recalculate equal splits if amount changed
        if ($oldAmount != $validated['amount'] && $expense->split_method === 'equal') {
            $memberCount = $trip->members()->count();
            $splitAmount = round($validated['amount'] / $memberCount, 2);
            $expense->splits()->update(['amount' => $splitAmount]);
        }

        ActivityLog::log($trip->id, Auth::id(), 'edited_expense', Expense::class, $expense->id, [
            'title' => $expense->title,
            'amount' => $expense->amount,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'expense' => $expense->fresh()]);
        }

        return redirect()->route('trips.finances', $trip)
            ->with('success', "'{$expense->title}' updated successfully.");
    }

    /**
     * Delete an expense.
     */
    public function destroy(Trip $trip, Expense $expense)
    {
        $this->authorize('delete', $expense);

        $expense->splits()->delete();
        $expense->delete();

        ActivityLog::log($trip->id, Auth::id(), 'deleted_expense', Expense::class, $expense->id, [
            'title' => $expense->title,
        ]);

        return redirect()->route('trips.finances', $trip)
            ->with('success', 'Expense deleted.');
    }

    /**
     * Scan a receipt using Gemini AI Vision.
     */
    public function scanReceipt(Request $request, Trip $trip)
    {
        $request->validate(['receipt' => ['required', 'image', 'max:10240']]);
        
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'AI Service not configured'], 500);
        }

        $imagePath = $request->file('receipt')->getRealPath();
        $imageData = base64_encode(file_get_contents($imagePath));

        $prompt = "Ekstrak informasi dari struk belanja ini secara detail. 
        Berikan jawaban dalam format JSON murni: 
        {
            \"title\": \"nama merchant atau nama toko\", 
            \"amount\": total_harga_angka_saja, 
            \"category\": \"salah satu dari: food, transport, accommodation, activity, shopping, other\", 
            \"date\": \"YYYY-MM-DD\",
            \"items\": [
                {\"name\": \"nama barang pertama\", \"quantity\": jumlah_barang_angka_saja, \"unit_price\": harga_satuan_angka_saja, \"price\": total_harga_barang_angka_saja},
                {\"name\": \"nama barang kedua\", \"quantity\": jumlah_barang_angka_saja, \"unit_price\": harga_satuan_angka_saja, \"price\": total_harga_barang_angka_saja}
            ]
        }. 
        Pastikan array 'items' berisi seluruh barang yang dibeli di struk. Hanya kembalikan string JSON saja tanpa blok markdown/backticks.";

        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $request->file('receipt')->getMimeType(),
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                $json = str_replace(['```json', '```'], '', $content);
                return response()->json(json_decode($json, true));
            }
            
            \Illuminate\Support\Facades\Log::error("Gemini API Error: " . $response->body());
            return response()->json(['error' => 'API menolak permintaan: ' . $response->status()], 500);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Receipt Scan Exception: " . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan internal: ' . $e->getMessage()], 500);
        }
    }
}
