<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ItineraryController extends Controller
{
    /**
     * Store a new itinerary item.
     */
    public function store(Request $request, Trip $trip, ItineraryDay $day)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'type' => ['required', 'string', 'in:flight,hotel,lodging,restaurant,activity,transport,other'],
            'status' => ['nullable', 'string', 'in:confirmed,tentative,voting'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'location_lat' => ['nullable', 'numeric'],
            'location_lng' => ['nullable', 'numeric'],
        ]);

        $validated['created_by'] = Auth::id();
        $validated['sort_order'] = $day->items()->max('sort_order') + 1;
        $validated['status'] = $validated['status'] ?? 'tentative';

        // Auto-fetch image from Unsplash
        if (config('services.unsplash.access_key')) {
            $queries = array_filter([$validated['location'] ?? null, $validated['title'] ?? null, $trip->destination, 'nature']);
            foreach ($queries as $query) {
                try {
                    $response = Http::withoutVerifying()->get('https://api.unsplash.com/search/photos', [
                        'query' => $query,
                        'client_id' => env('UNSPLASH_ACCESS_KEY'),
                        'per_page' => 1,
                        'orientation' => 'landscape',
                    ]);
                    
                    if ($response->successful() && !empty($response->json('results'))) {
                        $validated['image_url'] = $response->json('results')[0]['urls']['regular'];
                        break;
                    }
                } catch (\Exception $e) {
                    Log::warning('Unsplash image fetch failed during store: ' . $e->getMessage());
                }
            }
        }

        $item = $day->items()->create($validated);

        if ($item->status === 'voting') {
            $question = "Should we do: {$item->title}?";
            $existingPoll = \App\Models\Poll::where('trip_id', $trip->id)->where('question', $question)->first();
            if (!$existingPoll) {
                $poll = \App\Models\Poll::create([
                    'trip_id' => $trip->id,
                    'created_by' => Auth::id(),
                    'question' => $question,
                    'description' => "This activity was marked as 'Needs Vote' in the itinerary on Day {$day->day_number}. Location: " . ($item->location ?? 'TBD'),
                    'type' => 'single',
                    'status' => 'active',
                ]);
                $poll->options()->create(['title' => 'Yes', 'icon' => 'check_circle']);
                $poll->options()->create(['title' => 'No', 'icon' => 'cancel']);
                $poll->options()->create(['title' => 'Maybe', 'icon' => 'help']);
            }
        }

        ActivityLog::log($trip->id, Auth::id(), 'added_activity', ItineraryItem::class, $item->id, [
            'title' => $item->title,
            'day' => $day->day_number,
        ]);

        return redirect()->route('trips.itinerary', ['trip' => $trip, 'day' => $day->day_number])
            ->with('success', "'{$item->title}' added to Day {$day->day_number}!");
    }

    /**
     * Update an itinerary item.
     */
    public function update(Request $request, Trip $trip, ItineraryItem $item)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'type' => ['required', 'string', 'in:flight,hotel,lodging,restaurant,activity,transport,other'],
            'status' => ['nullable', 'string', 'in:confirmed,tentative,voting,cancelled'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'location_lat' => ['nullable', 'numeric'],
            'location_lng' => ['nullable', 'numeric'],
        ]);

        // Auto-fetch image from Unsplash if location/title changed or image is missing
        $needsNewImage = empty($item->image_url) || 
                         ($item->location !== ($validated['location'] ?? null)) || 
                         ($item->title !== ($validated['title'] ?? null));
                         
        if (config('services.unsplash.access_key') && $needsNewImage) {
            $queries = array_filter([$validated['location'] ?? null, $validated['title'] ?? null, $trip->destination, 'nature']);
            foreach ($queries as $query) {
                try {
                    $response = Http::withoutVerifying()->get('https://api.unsplash.com/search/photos', [
                        'query' => $query,
                        'client_id' => env('UNSPLASH_ACCESS_KEY'),
                        'per_page' => 1,
                        'orientation' => 'landscape',
                    ]);
                    
                    if ($response->successful() && !empty($response->json('results'))) {
                        $validated['image_url'] = $response->json('results')[0]['urls']['regular'];
                        break; // Stop once we find an image
                    }
                } catch (\Exception $e) {
                    Log::warning('Unsplash image fetch failed on update: ' . $e->getMessage());
                }
            }
        }

        $item->update($validated);

        if ($item->status === 'voting') {
            $question = "Should we do: {$item->title}?";
            $existingPoll = \App\Models\Poll::where('trip_id', $trip->id)->where('question', $question)->first();
            if (!$existingPoll) {
                $poll = \App\Models\Poll::create([
                    'trip_id' => $trip->id,
                    'created_by' => Auth::id(),
                    'question' => $question,
                    'description' => "This activity was marked as 'Needs Vote' in the itinerary on Day {$item->day->day_number}. Location: " . ($item->location ?? 'TBD'),
                    'type' => 'single',
                    'status' => 'active',
                ]);
                $poll->options()->create(['title' => 'Yes', 'icon' => 'check_circle']);
                $poll->options()->create(['title' => 'No', 'icon' => 'cancel']);
                $poll->options()->create(['title' => 'Maybe', 'icon' => 'help']);
            }
        }

        ActivityLog::log($trip->id, Auth::id(), 'updated_activity', ItineraryItem::class, $item->id, [
            'title' => $item->title,
        ]);

        $dayNumber = $item->day->day_number;
        return redirect()->route('trips.itinerary', ['trip' => $trip, 'day' => $dayNumber])
            ->with('success', "'{$item->title}' updated.");
    }

    /**
     * Delete an itinerary item.
     */
    public function destroy(Trip $trip, ItineraryItem $item)
    {
        $title = $item->title;
        $dayNumber = $item->day->day_number;

        ActivityLog::log($trip->id, Auth::id(), 'removed_activity', ItineraryItem::class, $item->id, [
            'title' => $title,
        ]);

        $item->delete();

        return redirect()->route('trips.itinerary', ['trip' => $trip, 'day' => $dayNumber])
            ->with('success', "'{$title}' removed from itinerary.");
    }

    /**
     * Reorder items via AJAX (drag-and-drop support).
     */
    public function reorder(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:itinerary_items,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['items'] as $itemData) {
            ItineraryItem::where('id', $itemData['id'])->update(['sort_order' => $itemData['sort_order']]);
        }

        return response()->json(['message' => 'Order updated']);
    }

    /**
     * Update notes for an itinerary day.
     */
    public function updateNotes(Request $request, Trip $trip, ItineraryDay $day)
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $day->update($validated);

        return back()->with('success', 'Day notes updated!');
    }
}
