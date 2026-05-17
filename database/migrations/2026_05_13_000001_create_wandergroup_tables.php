<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add avatar and timezone to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('timezone')->default('UTC')->after('avatar');
            $table->string('bio')->nullable();
            $table->json('travel_preferences')->nullable();
        });

        // Trips
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('destination');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('budget', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['planning', 'active', 'completed', 'cancelled'])->default('planning');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
            $table->index('created_by');
        });

        // Trip Members (pivot)
        Schema::create('trip_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['organizer', 'member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['trip_id', 'user_id']);
            $table->index('user_id');
        });

        // Trip Invitations
        Schema::create('trip_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->foreignId('invited_by')->constrained('users');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'status']);
        });

        // Itinerary Days
        Schema::create('itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('day_number');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['trip_id', 'date']);
            $table->index('trip_id');
        });

        // Itinerary Items
        Schema::create('itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_day_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('location_lat')->nullable();
            $table->string('location_lng')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('type', ['flight', 'hotel', 'restaurant', 'activity', 'transport', 'other'])->default('activity');
            $table->enum('status', ['confirmed', 'tentative', 'voting', 'cancelled'])->default('tentative');
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['itinerary_day_id', 'sort_order']);
        });

        // Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('category', ['accommodation', 'food', 'transport', 'activity', 'shopping', 'other'])->default('other');
            $table->string('icon')->nullable();
            $table->string('receipt_image')->nullable();
            $table->foreignId('paid_by')->constrained('users');
            $table->enum('split_method', ['equal', 'exact', 'percentage', 'shares'])->default('equal');
            $table->text('notes')->nullable();
            $table->date('expense_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['trip_id', 'expense_date']);
            $table->index('paid_by');
            $table->index('category');
        });

        // Expense Splits
        Schema::create('expense_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_settled')->default(false);
            $table->timestamps();

            $table->unique(['expense_id', 'user_id']);
            $table->index('user_id');
        });

        // Settlements
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user')->constrained('users');
            $table->foreignId('to_user')->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'status']);
        });

        // Polls (Voting)
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('description')->nullable();
            $table->enum('type', ['single', 'multiple'])->default('single');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamp('deadline')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['trip_id', 'status']);
        });

        // Poll Options
        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // Votes
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['poll_option_id', 'user_id']);
        });

        // Checklist Items
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('general');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_shared')->default(true);
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['trip_id', 'category']);
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // e.g. 'created_expense', 'voted', 'joined'
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('expense_splits');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('itinerary_items');
        Schema::dropIfExists('itinerary_days');
        Schema::dropIfExists('trip_invitations');
        Schema::dropIfExists('trip_user');
        Schema::dropIfExists('trips');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'timezone', 'bio', 'travel_preferences']);
        });
    }
};
