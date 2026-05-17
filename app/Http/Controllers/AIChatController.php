<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AIChatController extends Controller
{
    /**
     * Show WanderAI chat interface for a trip.
     */
    public function index(Trip $trip)
    {
        $trip->load(['members', 'itineraryDays.items', 'expenses', 'checklistItems']);

        $suggestions = $this->getSmartSuggestions($trip);

        return view('trips.ai-chat', compact('trip', 'suggestions'));
    }

    /**
     * Handle an AI chat message.
     * Returns JSON response for AJAX calls.
     */
    public function chat(Request $request, Trip $trip)
    {
        try {
            $request->validate([
                'message' => ['required', 'string', 'max:1000'],
            ]);

            $userMessage = $request->input('message');
            
            \Illuminate\Support\Facades\Log::info("WanderAI Chat Started", ['message' => $userMessage]);

            // Build trip context for the AI
            $context = $this->buildTripContext($trip);
            
            \Illuminate\Support\Facades\Log::info("Context Built Successfully");

            // Check API Key
            $apiKey = env('GEMINI_API_KEY');
            if (empty($apiKey)) {
                \Illuminate\Support\Facades\Log::error("GEMINI_API_KEY is missing in .env");
            }

            // Call the real AI Service (Gemini)
            $aiService = app(\App\Services\TripAIService::class);
            $response = $aiService->chat($userMessage, $context);

            \Illuminate\Support\Facades\Log::info("AI Response Received", ['type' => $response['type'] ?? 'unknown']);

            return response()->json([
                'reply' => $response['reply'],
                'suggestions' => $response['suggestions'],
                'type' => $response['type'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("AIChatController Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'reply' => "Aduh, sistem saya lagi agak pusing (Error: " . $e->getMessage() . "). Coba tanya lagi ya!",
                'suggestions' => [],
                'type' => 'error'
            ], 500);
        }
    }

    /**
     * Build a structured context string from trip data.
     */
    private function buildTripContext(Trip $trip): array
    {
        $trip->load(['members', 'itineraryDays.items', 'expenses.payer', 'checklistItems', 'polls.options']);

        return [
            'title' => $trip->title,
            'destination' => $trip->destination,
            'dates' => $trip->start_date->format('d M Y') . ' - ' . $trip->end_date->format('d M Y'),
            'duration' => $trip->start_date->diffInDays($trip->end_date) . ' hari',
            'budget' => rupiah($trip->budget ?? 0),
            'members' => $trip->members->pluck('name')->implode(', '),
            'member_count' => $trip->members->count(),
            'total_expenses' => rupiah($trip->expenses->sum('amount')),
            'activities_count' => $trip->itineraryDays->flatMap->items->count(),
            'checklist_progress' => $trip->checklistItems->count() > 0
                ? round($trip->checklistItems->where('is_checked', true)->count() / $trip->checklistItems->count() * 100)
                : 0,
        ];
    }

    /**
     * Get pre-built smart suggestions based on trip state.
     */
    private function getSmartSuggestions(Trip $trip): array
    {
        $suggestions = [];

        if ($trip->expenses->count() === 0) {
            $suggestions[] = ['icon' => 'payments', 'text' => 'Belum ada pengeluaran. Mulai catat!', 'action' => 'budget'];
        }

        if ($trip->checklistItems->where('is_checked', false)->count() > 0) {
            $unchecked = $trip->checklistItems->where('is_checked', false)->count();
            $suggestions[] = ['icon' => 'checklist', 'text' => "{$unchecked} item checklist belum selesai", 'action' => 'packing'];
        }

        $daysUntil = now()->diffInDays($trip->start_date, false);
        if ($daysUntil > 0 && $daysUntil <= 14) {
            $suggestions[] = ['icon' => 'schedule', 'text' => "Trip dimulai dalam {$daysUntil} hari!", 'action' => 'summary'];
        }

        return $suggestions;
    }
}
