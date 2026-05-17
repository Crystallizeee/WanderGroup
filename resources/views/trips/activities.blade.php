<x-app-layout>
    @php $title = $trip->title . ' - Activity Log'; @endphp

    <div class="mb-stack-lg">
        <h2 class="font-headline-lg text-headline-lg font-bold text-on-background mb-2">Notification & Activity Center</h2>
        <p class="text-on-surface-variant font-body-lg text-body-lg">Stay updated with your group's latest movements and alerts.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
        <!-- Urgent Alerts Section (Static for now, could be driven by DB later) -->
        <section class="lg:col-span-5 flex flex-col gap-stack-md">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-error">warning</span>
                <h3 class="font-headline-md text-headline-md text-on-background">Important Alerts</h3>
            </div>
            
            @if($trip->daysUntilTrip() > 0 && $trip->daysUntilTrip() < 7)
            <div class="bg-tertiary-container/20 text-on-tertiary-container rounded-xl p-5 shadow-[0px_4px_20px_rgba(0,0,0,0.04)] border-l-4 border-tertiary relative overflow-hidden transition-all hover:shadow-[0px_8px_30px_rgba(0,0,0,0.08)]">
                <div class="flex items-start gap-3 relative z-10">
                    <span class="material-symbols-outlined mt-1 text-tertiary">flight_takeoff</span>
                    <div>
                        <h4 class="font-label-md text-label-md font-bold mb-1">Trip is coming up!</h4>
                        <p class="font-body-md text-body-md text-on-tertiary-container/80">You depart in {{ $trip->daysUntilTrip() }} days. Make sure your checklist is complete.</p>
                    </div>
                </div>
            </div>
            @endif

            @php
                $unsettledExpenses = $trip->expenses()->whereDoesntHave('splits', function($q) {
                    $q->where('user_id', auth()->id())->where('is_settled', true);
                })->exists();
            @endphp
            @if($unsettledExpenses)
            <div class="bg-error-container text-on-error-container rounded-xl p-5 shadow-[0px_4px_20px_rgba(0,0,0,0.04)] border-l-4 border-error relative overflow-hidden transition-all hover:shadow-[0px_8px_30px_rgba(0,0,0,0.08)]">
                <div class="flex items-start gap-3 relative z-10">
                    <span class="material-symbols-outlined mt-1">account_balance_wallet</span>
                    <div>
                        <h4 class="font-label-md text-label-md font-bold mb-1">Unsettled Expenses</h4>
                        <p class="font-body-md text-body-md text-on-error-container/80 mb-3">You have pending expenses to settle up.</p>
                        <a href="{{ route('trips.settlements', $trip) }}" class="bg-error text-on-error px-4 py-1.5 rounded-full font-label-sm hover:bg-error/90 transition-colors inline-block">Settle Now</a>
                    </div>
                </div>
            </div>
            @endif

            <div class="bg-surface-container-lowest rounded-xl p-5 shadow-[0px_4px_20px_rgba(0,0,0,0.04)] border border-outline-variant relative overflow-hidden transition-all hover:shadow-[0px_8px_30px_rgba(0,0,0,0.08)]">
                <div class="flex items-start gap-3 relative z-10">
                    <span class="material-symbols-outlined mt-1 text-primary">auto_awesome</span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 bg-primary/10 text-primary rounded-full font-label-sm text-[10px] uppercase tracking-wider">WanderAI</span>
                            <h4 class="font-label-md text-label-md font-bold">Plan your trip faster</h4>
                        </div>
                        <p class="font-body-md text-body-md text-on-surface-variant mb-3">Ask WanderAI to build your itinerary or find places to eat.</p>
                        <a href="{{ route('trips.ai', $trip) }}" class="bg-primary-container text-on-primary-container px-4 py-1.5 rounded-full font-label-sm hover:opacity-90 transition-opacity inline-block">Ask AI</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Activity Log Section -->
        <section class="lg:col-span-7 flex flex-col">
            <div class="flex items-center justify-between mb-stack-md">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">history</span>
                    <h3 class="font-headline-md text-headline-md text-on-background">Activity Log</h3>
                </div>
            </div>
            
            <div class="bg-surface-container-lowest rounded-xl shadow-[0px_4px_20px_rgba(0,0,0,0.04)] p-6 flex flex-col gap-stack-md border border-surface-variant">
                @if($activities->isEmpty())
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-[48px] text-outline-variant mb-2">pending_actions</span>
                        <p class="text-on-surface-variant font-body-md">No activities recorded yet.</p>
                    </div>
                @else
                    @foreach($activities as $log)
                    <div class="flex gap-4 items-start pb-4 border-b border-surface-variant last:border-0 last:pb-0">
                        <div class="w-10 h-10 rounded-full {{ $log->user ? 'bg-surface-variant' : 'bg-primary-container' }} flex items-center justify-center shrink-0 overflow-hidden">
                            @if($log->user)
                                <div class="w-full h-full flex items-center justify-center font-bold text-sm bg-surface-variant text-on-surface-variant">
                                    {{ $log->user->initials }}
                                </div>
                            @else
                                <span class="material-symbols-outlined text-[20px] text-primary">auto_awesome</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="font-body-md text-body-md text-on-background">
                                <span class="font-semibold">{{ $log->user ? $log->user->name : 'System/AI' }}</span> 
                                
                                @if($log->action === 'added_itinerary_item')
                                    added <span class="font-semibold text-primary">{{ $log->metadata['title'] ?? 'an item' }}</span> to the itinerary.
                                @elseif($log->action === 'removed_itinerary_item')
                                    removed an item from the itinerary.
                                @elseif($log->action === 'added_expense')
                                    added an expense: <span class="font-semibold text-tertiary">{{ $log->metadata['title'] ?? 'Expense' }}</span>.
                                @elseif($log->action === 'removed_expense')
                                    removed an expense.
                                @elseif($log->action === 'added_checklist_item')
                                    added <span class="font-semibold text-secondary">{{ $log->metadata['title'] ?? 'an item' }}</span> to the checklist.
                                @elseif($log->action === 'checked_item')
                                    checked off <span class="font-semibold text-secondary">{{ $log->metadata['title'] ?? 'an item' }}</span>.
                                @elseif($log->action === 'unchecked_item')
                                    unchecked <span class="font-semibold text-secondary">{{ $log->metadata['title'] ?? 'an item' }}</span>.
                                @elseif($log->action === 'uploaded_document')
                                    uploaded a document: <span class="font-semibold text-primary">{{ $log->metadata['title'] ?? 'File' }}</span>.
                                @elseif($log->action === 'deleted_document')
                                    deleted a document.
                                @else
                                    {{ str_replace('_', ' ', $log->action) }}
                                @endif
                            </p>
                            <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">{{ $log->created_at->diffForHumans() }}</p>
                            
                            @if($log->action === 'uploaded_document' && isset($log->metadata['title']))
                            <div class="mt-2 inline-flex items-center gap-2 bg-surface-container-low px-3 py-2 rounded-lg border border-outline-variant/50">
                                <span class="material-symbols-outlined text-outline text-sm">receipt</span>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">{{ $log->metadata['title'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
            
            <div class="mt-4 flex justify-center">
                {{ $activities->links() }}
            </div>
        </section>
    </div>
</x-app-layout>
