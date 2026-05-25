<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\VotingController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\SettlementController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\AIChatController;
use App\Http\Middleware\EnsureTripMember;
use App\Http\Middleware\EnsureTripOrganizer;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(SecurityHeaders::class)->group(function () {

    // ── Guest Routes ──
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    // ── Trip Invitation (can be accessed by guest or auth) ──
    Route::get('/invite/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
    Route::post('/invite/{token}/decline', [InvitationController::class, 'decline'])->name('invitation.decline');

    // ── Authenticated Routes ──
    Route::middleware(['auth', 'verified'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [TripController::class, 'dashboard'])->name('dashboard');

        // Explore
        Route::get('/explore', [ExploreController::class, 'index'])->name('explore');

        // Trip CRUD
        Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');

        // Trip-scoped routes (require membership)
        Route::middleware(EnsureTripMember::class)->prefix('trips/{trip}')->name('trips.')->group(function () {

            // Views
            Route::get('/', [TripController::class, 'show'])->name('show');
            Route::get('/activities', [TripController::class, 'activities'])->name('activities');
            Route::get('/itinerary', [TripController::class, 'itinerary'])->name('itinerary');
            Route::get('/finances', [TripController::class, 'finances'])->name('finances');
            Route::get('/budget', [TripController::class, 'budget'])->name('budget');
            Route::post('/budget/categories', [TripController::class, 'updateCategoryBudgets'])->name('budget.categories');
            Route::get('/voting', [TripController::class, 'voting'])->name('voting');
            Route::get('/checklist', [TripController::class, 'checklist'])->name('checklist');
            Route::get('/members', [TripController::class, 'members'])->name('members');

            // Itinerary CRUD
            Route::post('/itinerary/{day}/items', [ItineraryController::class, 'store'])->name('itinerary.store');
            Route::put('/itinerary/items/{item}', [ItineraryController::class, 'update'])->name('itinerary.update');
            Route::delete('/itinerary/items/{item}', [ItineraryController::class, 'destroy'])->name('itinerary.destroy');
            Route::post('/itinerary/reorder', [ItineraryController::class, 'reorder'])->name('itinerary.reorder');
            Route::post('/itinerary/{day}/notes', [ItineraryController::class, 'updateNotes'])->name('itinerary.notes');

            // Expenses
            Route::post('/expenses/scan', [ExpenseController::class, 'scanReceipt'])->name('expenses.scan');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
            Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

            // Settlements
            Route::get('/settlements', [SettlementController::class, 'index'])->name('settlements');
            Route::post('/settlements', [SettlementController::class, 'settle'])->name('settlements.settle');
            Route::delete('/settlements/{settlement}', [SettlementController::class, 'destroy'])->name('settlements.destroy');

            // Voting
            Route::post('/polls', [VotingController::class, 'storePoll'])->name('polls.store');
            Route::post('/polls/options/{option}/vote', [VotingController::class, 'vote'])->name('polls.vote');
            Route::post('/polls/{poll}/close', [VotingController::class, 'closePoll'])->name('polls.close');

            // Checklist
            Route::post('/checklist', [ChecklistController::class, 'store'])->name('checklist.store');
            Route::post('/checklist/{item}/toggle', [ChecklistController::class, 'toggle'])->name('checklist.toggle');
            Route::delete('/checklist/{item}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');

            // Invitations
            Route::post('/invite', [InvitationController::class, 'invite'])->name('invite');

            // WanderAI Chat
            Route::get('/ai', [AIChatController::class, 'index'])->name('ai');
            Route::post('/ai/chat', [AIChatController::class, 'chat'])->name('ai.chat');

            // Document Vault
            Route::get('/documents', [App\Http\Controllers\DocumentController::class, 'index'])->name('documents');
            Route::post('/documents', [App\Http\Controllers\DocumentController::class, 'store'])->name('documents.store');
            Route::get('/documents/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])->name('documents.download');
            Route::delete('/documents/{document}', [App\Http\Controllers\DocumentController::class, 'destroy'])->name('documents.destroy');

            // Trip Memories
            Route::get('/memories', [App\Http\Controllers\MemoryController::class, 'index'])->name('memories');
            Route::post('/memories', [App\Http\Controllers\MemoryController::class, 'store'])->name('memories.store');
            Route::get('/memories/{memory}/image', [App\Http\Controllers\MemoryController::class, 'serveImage'])->name('memories.image');
            Route::post('/memories/{memory}/highlight', [App\Http\Controllers\MemoryController::class, 'toggleHighlight'])->name('memories.highlight');
            Route::delete('/memories/{memory}', [App\Http\Controllers\MemoryController::class, 'destroy'])->name('memories.destroy');

            // Organizer-only routes
            Route::middleware(EnsureTripOrganizer::class)->group(function () {
                Route::get('/edit', [TripController::class, 'edit'])->name('edit');
                Route::put('/', [TripController::class, 'update'])->name('update');
                Route::delete('/', [TripController::class, 'destroy'])->name('destroy');

                // Member Management
                Route::post('/members/{user}/role', [TripController::class, 'updateMemberRole'])->name('members.role');
                Route::delete('/members/{user}', [TripController::class, 'removeMember'])->name('members.remove');
            });
        });

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

});

// Public Routes
Route::get('/join/{code}', [InvitationController::class, 'join'])->name('trips.join');

require __DIR__.'/auth.php';
