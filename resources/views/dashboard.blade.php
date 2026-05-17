<x-app-layout>
    @php $title = 'Dashboard'; @endphp

    {{-- Header Section --}}
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-stack-lg">
        <div>
            <h1 class="font-display text-display-lg text-on-background mb-1">
                Welcome back, {{ Auth::user()->name }}!
            </h1>
            <p class="font-body text-body-lg text-on-surface-variant">Ready for your next adventure?</p>
        </div>
        <a href="{{ route('trips.create') }}" class="btn-primary shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined">add</span>
            Mulai Perjalanan Baru
        </a>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
        {{-- Left Column: Active Trips & History --}}
        <div class="lg:col-span-8 flex flex-col gap-stack-lg">

            {{-- Active Trips Section --}}
            <section>
                <div class="flex items-center justify-between mb-stack-md">
                    <h3 class="font-display text-headline-md text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">flight_takeoff</span>
                        Active Trips
                    </h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($activeTrips as $index => $trip)
                        {{-- Premium Trip Card --}}
                        <a href="{{ route('trips.show', $trip) }}"
                           class="card group p-0 overflow-hidden flex flex-col border-0 bg-white transition-all hover:-translate-y-1">
                            <div class="h-48 relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent z-10"></div>
                                @if($trip->cover_image)
                                    <img src="{{ str_starts_with($trip->cover_image, 'http') ? $trip->cover_image : asset('storage/' . $trip->cover_image) }}" 
                                         alt="{{ $trip->title }}" 
                                         class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                         onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden')">
                                    <div class="hidden absolute inset-0 bg-gradient-to-br from-primary to-secondary"></div>
                                @else
                                    <div class="absolute inset-0 bg-gradient-to-br from-primary to-secondary"></div>
                                @endif
                                <div class="absolute bottom-4 left-4 z-20">
                                    <div class="inline-flex items-center px-2.5 py-1 rounded-full bg-white/20 backdrop-blur-md text-white font-label text-[10px] uppercase tracking-widest mb-1">
                                        {{ $trip->status }}
                                    </div>
                                    <h4 class="font-display text-headline-md text-white leading-tight">{{ $trip->title }}</h4>
                                </div>
                            </div>
                            <div class="p-5 space-y-4">
                                <div class="flex justify-between items-center text-outline font-label text-label-sm">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px]">calendar_month</span>
                                        {{ $trip->start_date->format('d M') }} - {{ $trip->end_date->format('d M, Y') }}
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[16px]">location_on</span>
                                        {{ $trip->destination }}
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 border-t border-surface-variant/30">
                                    <div class="flex items-center -space-x-2">
                                        @foreach($trip->members->take(4) as $member)
                                            <div class="w-8 h-8 rounded-full border-2 border-white bg-surface-variant flex items-center justify-center text-[10px] font-bold text-on-surface-variant shadow-sm">
                                                {{ $member->initials }}
                                            </div>
                                        @endforeach
                                        @if($trip->members->count() > 4)
                                            <div class="w-8 h-8 rounded-full border-2 border-white bg-primary-container text-on-primary-container flex items-center justify-center text-[10px] font-bold shadow-sm">
                                                +{{ $trip->members->count() - 4 }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="material-symbols-outlined text-primary group-hover:translate-x-1 transition-transform">arrow_right_alt</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="md:col-span-2 bg-surface-bright rounded-3xl border-2 border-dashed border-outline-variant p-16 flex flex-col items-center justify-center text-center">
                            <div class="w-20 h-20 rounded-full bg-primary/5 flex items-center justify-center mb-6">
                                <span class="material-symbols-outlined text-[40px] text-primary/30">explore</span>
                            </div>
                            <h4 class="font-display text-headline-md text-on-surface mb-2">Adventure Awaits!</h4>
                            <p class="font-body text-body-md text-on-surface-variant mb-6 max-w-sm">You haven't planned any trips yet. Create your first journey and invite your friends!</p>
                            <a href="{{ route('trips.create') }}" class="btn-primary">
                                <span class="material-symbols-outlined">add_location_alt</span>
                                Start a New Trip
                            </a>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- History Section --}}
            @if($completedTrips->isNotEmpty())
            <section>
                <h3 class="font-display text-headline-md text-on-surface mb-stack-md flex items-center gap-2">
                    <span class="material-symbols-outlined text-outline">history</span>
                    Travel History
                </h3>
                <div class="card p-2 border-0 bg-white shadow-elevation-1">
                    <div class="divide-y divide-surface-variant/30">
                        @foreach($completedTrips as $trip)
                        <a href="{{ route('trips.show', $trip) }}" class="flex items-center justify-between p-4 hover:bg-surface-container-lowest transition-all rounded-xl group">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-surface-variant/50 overflow-hidden flex items-center justify-center text-outline group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined">map</span>
                                </div>
                                <div>
                                    <h4 class="font-label text-label-md text-on-surface group-hover:text-primary transition-colors">{{ $trip->title }}</h4>
                                    <p class="font-body text-[12px] text-on-surface-variant">{{ $trip->start_date->format('M Y') }} • {{ $trip->members->count() }} Travelers</p>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-outline opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all">chevron_right</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </section>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-4 flex flex-col gap-stack-lg">
            {{-- Quick Stats Card (Premium Gradient) --}}
            <section class="relative p-6 rounded-3xl overflow-hidden shadow-elevation-3 bg-gradient-to-br from-primary via-primary to-secondary text-white">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                <h3 class="font-label text-[10px] uppercase tracking-[0.2em] mb-1 opacity-70">Explorer Stats</h3>
                <p class="font-display text-headline-md mb-6">Your Journey</p>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-end border-b border-white/10 pb-3">
                        <div class="space-y-1">
                            <p class="text-[12px] opacity-80">Total Trips</p>
                            <p class="font-display text-headline-sm leading-none">{{ Auth::user()->trips()->count() }}</p>
                        </div>
                        <span class="material-symbols-outlined opacity-30">travel_explore</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-[12px] opacity-80">Active</p>
                            <p class="font-display text-headline-sm leading-none">{{ $activeTrips->count() }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[12px] opacity-80">Completed</p>
                            <p class="font-display text-headline-sm leading-none">{{ $completedTrips->count() }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Recent Activity Log (Level 1 Shadow) --}}
            <section class="card border-0 bg-white">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-headline text-label-md font-bold">Group Activity</h3>
                    <span class="px-2 py-0.5 rounded bg-primary/10 text-primary font-label text-[10px] uppercase">Live</span>
                </div>
                <div class="space-y-6 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-[1px] before:bg-surface-variant/50">
                    @forelse($recentActivity->take(6) as $log)
                    <div class="relative pl-8">
                        <span class="absolute left-2 top-1.5 w-2 h-2 rounded-full bg-primary ring-4 ring-white"></span>
                        <p class="font-body text-[13px] text-on-surface leading-tight">
                            <span class="font-bold">{{ $log->user->name }}</span>
                            <span class="text-on-surface-variant">{{ str_replace('_', ' ', $log->action) }}</span>
                            @if($log->metadata)
                                <span class="text-primary font-medium">{{ $log->metadata['title'] ?? '' }}</span>
                            @endif
                        </p>
                        <p class="font-label text-[10px] text-outline mt-1 uppercase">{{ $log->created_at->diffForHumans() }}</p>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <p class="font-body text-body-md text-on-surface-variant italic">No activity yet.</p>
                    </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
