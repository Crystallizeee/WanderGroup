<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\ChecklistItem;
use App\Models\ActivityLog;
use App\Events\TripUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChecklistController extends Controller
{
    /**
     * Store a new checklist item.
     */
    public function store(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $validated['trip_id'] = $trip->id;
        $validated['created_by'] = Auth::id();
        $validated['is_shared'] = $request->has('is_shared') ? $request->boolean('is_shared') : true;
        $validated['quantity'] = $validated['quantity'] ?? 1;

        if (!$validated['is_shared'] && empty($validated['assigned_to'])) {
            // Create a personal item for EVERY member
            foreach ($trip->members as $member) {
                $data = $validated;
                $data['assigned_to'] = $member->id;
                ChecklistItem::create($data);
            }
            
            ActivityLog::log($trip->id, Auth::id(), 'added_checklist_item', ChecklistItem::class, 0, [
                'title' => $validated['title'] . ' (Personal for everyone)',
            ]);

            return back()->with('success', "'{$validated['title']}' added to checklist for each member.");
        }

        // Standard creation (Shared item OR assigned to a specific single person)
        $item = ChecklistItem::create($validated);

        ActivityLog::log($trip->id, Auth::id(), 'added_checklist_item', ChecklistItem::class, $item->id, [
            'title' => $item->title,
        ]);

        return back()->with('success', "'{$item->title}' added to checklist.");
    }

    /**
     * Toggle checked state of a checklist item.
     */
    public function toggle(Request $request, Trip $trip, ChecklistItem $item)
    {
        abort_unless($item->trip_id === $trip->id, 404);
        // Personal items can only be toggled by their owner
        if (!$item->is_shared && $item->assigned_to !== Auth::id()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'You can only check your own personal items.'], 403);
            }
            return back()->with('error', 'You can only check your own personal items.');
        }

        $item->update(['is_checked' => !$item->is_checked]);

        $action = $item->is_checked ? 'checked_item' : 'unchecked_item';
        ActivityLog::log($trip->id, Auth::id(), $action, ChecklistItem::class, $item->id, [
            'title' => $item->title,
        ]);

        broadcast(new TripUpdated($trip->id, 'checklist_toggled', [
            'item_id' => $item->id,
            'is_checked' => $item->is_checked,
            'user' => Auth::user()->name
        ]))->toOthers();

        if ($request->wantsJson()) {
            return response()->json(['checked' => $item->is_checked]);
        }

        return back();
    }

    /**
     * Delete a checklist item.
     */
    public function destroy(Trip $trip, ChecklistItem $item)
    {
        abort_unless($item->trip_id === $trip->id, 404);
        // Only item creator, assigned owner, or trip organizers can delete
        $user = Auth::user();
        $isOwnerOrCreator = $item->created_by === $user->id || $item->assigned_to === $user->id;
        if (!$isOwnerOrCreator && !$trip->isOrganizer($user)) {
            abort(403, 'You are not authorized to delete this item.');
        }

        $title = $item->title;
        $item->delete();

        ActivityLog::log($trip->id, Auth::id(), 'removed_checklist_item', ChecklistItem::class, $item->id, [
            'title' => $title,
        ]);

        return back()->with('success', "'{$title}' removed.");
    }
}
