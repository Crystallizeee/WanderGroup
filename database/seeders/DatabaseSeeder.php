<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Trip;
use App\Models\ItineraryItem;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Models\ChecklistItem;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create users
        $alex = User::create([
            'name' => 'Alex Johnson',
            'email' => 'alex@wandergroup.test',
            'password' => Hash::make('password'),
            'timezone' => 'Asia/Jakarta',
        ]);

        $sarah = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'sarah@wandergroup.test',
            'password' => Hash::make('password'),
        ]);

        $david = User::create([
            'name' => 'David Chen',
            'email' => 'david@wandergroup.test',
            'password' => Hash::make('password'),
        ]);

        $emma = User::create([
            'name' => 'Emma Wilson',
            'email' => 'emma@wandergroup.test',
            'password' => Hash::make('password'),
        ]);

        // Create Bali trip
        $baliTrip = Trip::create([
            'title' => 'Bali Summer Break',
            'destination' => 'Bali, Indonesia',
            'description' => 'A week of relaxation, surfing, and exploring temples in beautiful Bali.',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(37),
            'budget' => 75000000,
            'currency' => 'IDR',
            'status' => 'planning',
            'created_by' => $alex->id,
        ]);

        $baliTrip->members()->attach($alex->id, ['role' => 'organizer']);
        $baliTrip->members()->attach($sarah->id, ['role' => 'member']);
        $baliTrip->members()->attach($david->id, ['role' => 'member']);
        $baliTrip->members()->attach($emma->id, ['role' => 'member']);

        // Create itinerary days
        for ($i = 1; $i <= 7; $i++) {
            $day = $baliTrip->itineraryDays()->create([
                'date' => now()->addDays(29 + $i),
                'day_number' => $i,
            ]);

            if ($i === 1) {
                $day->items()->create([
                    'title' => 'Flight to DPS',
                    'description' => 'Garuda Indonesia GA402 • Terminal 3',
                    'type' => 'flight',
                    'status' => 'confirmed',
                    'start_time' => '08:00',
                    'end_time' => '10:30',
                    'sort_order' => 1,
                    'created_by' => $alex->id,
                ]);
                $day->items()->create([
                    'title' => 'W Bali - Seminyak',
                    'description' => 'Jl. Petitenget Kerobokan',
                    'location' => 'Seminyak, Bali',
                    'type' => 'hotel',
                    'status' => 'confirmed',
                    'start_time' => '12:30',
                    'sort_order' => 2,
                    'created_by' => $alex->id,
                ]);
                $day->items()->create([
                    'title' => 'Afternoon Activity',
                    'description' => 'Choose what the group should do before dinner.',
                    'type' => 'activity',
                    'status' => 'voting',
                    'start_time' => '15:00',
                    'sort_order' => 3,
                    'created_by' => $sarah->id,
                ]);
            }
        }

        // Create expenses
        $expense1 = Expense::create([
            'trip_id' => $baliTrip->id,
            'title' => 'Sushi Dinner in Shibuya',
            'amount' => 1800000,
            'category' => 'food',
            'paid_by' => $alex->id,
            'split_method' => 'equal',
            'expense_date' => now()->subDays(2),
        ]);

        foreach ([$alex, $sarah, $david, $emma] as $member) {
            ExpenseSplit::create([
                'expense_id' => $expense1->id,
                'user_id' => $member->id,
                'amount' => 450000,
            ]);
        }

        $expense2 = Expense::create([
            'trip_id' => $baliTrip->id,
            'title' => 'Shinkansen Tickets',
            'amount' => 5200000,
            'category' => 'transport',
            'paid_by' => $alex->id,
            'split_method' => 'equal',
            'expense_date' => now()->subDays(3),
        ]);

        foreach ([$alex, $sarah, $david, $emma] as $member) {
            ExpenseSplit::create([
                'expense_id' => $expense2->id,
                'user_id' => $member->id,
                'amount' => 1300000,
            ]);
        }

        // Create poll
        $poll = Poll::create([
            'trip_id' => $baliTrip->id,
            'question' => 'Afternoon Activity',
            'description' => 'Choose what to do on Day 1 afternoon.',
            'type' => 'single',
            'status' => 'active',
            'deadline' => now()->addDays(7),
            'created_by' => $sarah->id,
        ]);

        $surf = PollOption::create(['poll_id' => $poll->id, 'title' => 'Surf Lesson', 'icon' => 'surfing']);
        $spa = PollOption::create(['poll_id' => $poll->id, 'title' => 'Spa Treatment', 'icon' => 'spa']);

        Vote::create(['poll_option_id' => $surf->id, 'user_id' => $alex->id]);
        Vote::create(['poll_option_id' => $surf->id, 'user_id' => $david->id]);
        Vote::create(['poll_option_id' => $surf->id, 'user_id' => $emma->id]);
        Vote::create(['poll_option_id' => $spa->id, 'user_id' => $sarah->id]);

        // Create checklist items
        foreach ([
            ['title' => 'Sunscreen SPF 50+', 'category' => 'essentials', 'assigned_to' => $alex->id],
            ['title' => 'Swimsuit', 'category' => 'clothing', 'assigned_to' => null, 'is_checked' => true],
            ['title' => 'First Aid Kit', 'category' => 'essentials', 'assigned_to' => $sarah->id],
            ['title' => 'Reef-safe Sunblock', 'category' => 'essentials', 'assigned_to' => $emma->id],
            ['title' => 'Snorkeling Gear', 'category' => 'equipment', 'assigned_to' => $david->id],
            ['title' => 'Travel Adapter', 'category' => 'electronics', 'assigned_to' => null],
        ] as $item) {
            ChecklistItem::create(array_merge($item, [
                'trip_id' => $baliTrip->id,
                'created_by' => $alex->id,
                'is_checked' => $item['is_checked'] ?? false,
            ]));
        }

        // Create activity logs
        ActivityLog::log($baliTrip->id, $sarah->id, 'voted', Poll::class, $poll->id, ['title' => 'Afternoon Activity']);
        ActivityLog::log($baliTrip->id, $alex->id, 'added_expense', Expense::class, $expense2->id, ['title' => 'Shinkansen Tickets', 'amount' => 5200000]);
        ActivityLog::log($baliTrip->id, $emma->id, 'joined_trip', Trip::class, $baliTrip->id, ['title' => 'Bali Summer Break']);

        // Create second trip (completed)
        $parisTrip = Trip::create([
            'title' => 'Paris Getaway',
            'destination' => 'Paris, France',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonths(3)->addDays(5),
            'budget' => 45000000,
            'status' => 'completed',
            'created_by' => $alex->id,
        ]);
        $parisTrip->members()->attach($alex->id, ['role' => 'organizer']);
        $parisTrip->members()->attach($sarah->id, ['role' => 'member']);
    }
}
