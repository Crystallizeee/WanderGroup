<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VotingController extends Controller
{
    /**
     * Create a new poll.
     */
    public function storePoll(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:single,multiple'],
            'deadline' => ['nullable', 'date', 'after:now'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.title' => ['required', 'string', 'max:255'],
            'options.*.description' => ['nullable', 'string', 'max:500'],
            'options.*.icon' => ['nullable', 'string', 'max:50'],
        ]);

        return DB::transaction(function () use ($validated, $trip) {
            $poll = Poll::create([
                'trip_id' => $trip->id,
                'question' => $validated['question'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'deadline' => $validated['deadline'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['options'] as $option) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'title' => $option['title'],
                    'description' => $option['description'] ?? null,
                    'icon' => $option['icon'] ?? null,
                ]);
            }

            ActivityLog::log($trip->id, Auth::id(), 'created_poll', Poll::class, $poll->id, [
                'title' => $poll->question,
            ]);

            return redirect()->route('trips.voting', $trip)
                ->with('success', 'Poll created successfully!');
        });
    }

    /**
     * Cast a vote on a poll option.
     */
    public function vote(Request $request, Trip $trip, PollOption $option)
    {
        $poll = $option->poll;

        // Check poll is still active
        if ($poll->status !== 'active') {
            return back()->with('error', 'This poll has been closed.');
        }

        if ($poll->deadline && $poll->deadline->isPast()) {
            $poll->update(['status' => 'closed']);
            return back()->with('error', 'This poll has expired.');
        }

        $userId = Auth::id();

        // For single-choice polls: remove previous votes on this poll
        if ($poll->type === 'single') {
            $existingVotes = Vote::whereIn('poll_option_id', $poll->options->pluck('id'))
                ->where('user_id', $userId)
                ->get();

            // Toggle: if already voted for this option, remove
            if ($existingVotes->where('poll_option_id', $option->id)->isNotEmpty()) {
                Vote::where('poll_option_id', $option->id)->where('user_id', $userId)->delete();
                return back()->with('success', 'Vote removed.');
            }

            // Remove any other votes on this poll
            Vote::whereIn('poll_option_id', $poll->options->pluck('id'))
                ->where('user_id', $userId)
                ->delete();
        }

        // For multiple-choice: toggle the specific option
        if ($poll->type === 'multiple') {
            $existing = Vote::where('poll_option_id', $option->id)->where('user_id', $userId)->first();
            if ($existing) {
                $existing->delete();
                return back()->with('success', 'Vote removed.');
            }
        }

        Vote::create([
            'poll_option_id' => $option->id,
            'user_id' => $userId,
        ]);

        ActivityLog::log($trip->id, $userId, 'voted', Poll::class, $poll->id, [
            'title' => $poll->question,
            'option' => $option->title,
        ]);

        return back()->with('success', "Voted for '{$option->title}'!");
    }

    /**
     * Close a poll (organizer only).
     */
    public function closePoll(Request $request, Trip $trip, Poll $poll)
    {
        if ($poll->created_by !== Auth::id() && !$trip->isOrganizer(Auth::user())) {
            abort(403, 'Only the poll creator or trip organizer can close polls.');
        }

        $poll->update(['status' => 'closed']);

        ActivityLog::log($trip->id, Auth::id(), 'closed_poll', Poll::class, $poll->id, [
            'title' => $poll->question,
        ]);

        return back()->with('success', 'Poll closed.');
    }
}
