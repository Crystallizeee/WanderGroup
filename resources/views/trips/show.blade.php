<x-app-layout>
    @php $title = $trip->title; @endphp

    @if($trip->cover_image)
    {{-- Hero Cover Image Header --}}
    <header class="mb-stack-lg -mx-container-padding-mobile md:-mx-container-padding-desktop -mt-stack-lg relative rounded-b-3xl overflow-hidden">
        <div class="h-56 md:h-72 relative">
            <img src="{{ str_starts_with($trip->cover_image, 'http') ? $trip->cover_image : asset('storage/' . $trip->cover_image) }}" 
                 alt="{{ $trip->title }}" 
                 class="absolute inset-0 w-full h-full object-cover"
                 onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden')">
            <div class="hidden absolute inset-0 bg-gradient-to-br from-primary to-secondary"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-white font-label text-label-sm mb-2">
                            <span class="material-symbols-outlined text-[14px] mr-1">calendar_today</span>
                            {{ $trip->start_date->format('M d') }} - {{ $trip->end_date->format('M d, Y') }}
                        </div>
                        <h2 class="font-headline text-headline-lg text-white drop-shadow-lg">{{ $trip->title }}</h2>
                        <p class="font-body text-body-lg text-white/80 mt-1">{{ $trip->destination }}</p>
                    </div>
                    <div class="flex gap-3 flex-wrap">
                        @if($trip->isOrganizer(Auth::user()))
                        <a href="{{ route('trips.edit', $trip) }}" class="btn-secondary bg-white/10 backdrop-blur-md border-white/20 text-white hover:bg-white/20">
                            <span class="material-symbols-outlined">edit</span> Edit Trip
                        </a>
                        @endif
                        <a href="{{ route('trips.ai', $trip) }}" class="btn-secondary bg-white/10 backdrop-blur-md border-white/20 text-white hover:bg-white/20">
                            <span class="material-symbols-outlined">auto_awesome</span> WanderAI
                        </a>
                        <a href="{{ route('trips.itinerary', $trip) }}" class="flex items-center gap-2 px-4 py-2 bg-white text-primary rounded-full font-label text-label-md hover:bg-white/90 transition-colors shadow-md">
                            <span class="material-symbols-outlined">event_note</span> Itinerary
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    @else
    {{-- Fallback Plain Header --}}
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-stack-lg">
        <div>
            <div class="inline-flex items-center px-3 py-1 rounded-full bg-surface-container-high text-on-surface-variant font-label text-label-sm mb-2">
                <span class="material-symbols-outlined text-[14px] mr-1">calendar_today</span>
                {{ $trip->start_date->format('M d') }} - {{ $trip->end_date->format('M d, Y') }}
            </div>
            <h2 class="font-headline text-headline-lg text-on-surface">{{ $trip->title }}</h2>
            <p class="font-body text-body-lg text-on-surface-variant mt-1">{{ $trip->destination }}</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            @if($trip->isOrganizer(Auth::user()))
            <a href="{{ route('trips.edit', $trip) }}" class="btn-secondary">
                <span class="material-symbols-outlined">edit</span> Edit Trip
            </a>
            @endif
            <a href="{{ route('trips.ai', $trip) }}" class="btn-secondary bg-gradient-to-r from-primary/10 to-secondary/10 border border-primary/20 text-primary">
                <span class="material-symbols-outlined">auto_awesome</span> WanderAI
            </a>
            <a href="{{ route('trips.itinerary', $trip) }}" class="btn-primary">
                <span class="material-symbols-outlined">event_note</span> Itinerary
            </a>
            <a href="{{ route('trips.finances', $trip) }}" class="btn-secondary">
                <span class="material-symbols-outlined">payments</span> Finances
            </a>
        </div>
    </header>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
        {{-- Main Content --}}
        <div class="lg:col-span-2 flex flex-col gap-stack-lg">

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="card text-center">
                    <div class="font-headline text-headline-md text-primary">{{ $trip->memberCount() }}</div>
                    <p class="font-label text-label-sm text-on-surface-variant">Members</p>
                </div>
                <div class="card text-center">
                    <div class="font-headline text-headline-md text-secondary">{{ $trip->duration() }}</div>
                    <p class="font-label text-label-sm text-on-surface-variant">Days</p>
                </div>
                <div class="card text-center">
                    <div class="font-headline text-headline-md text-tertiary">{{ rupiah($totalSpent) }}</div>
                    <p class="font-label text-label-sm text-on-surface-variant">Total Spent</p>
                </div>
                <div class="card text-center">
                    @php $net = $memberBalances['net'][Auth::id()] ?? 0; @endphp
                    <div class="font-headline text-headline-md {{ $net >= 0 ? 'text-primary' : 'text-error' }}">{{ rupiah_signed($net) }}</div>
                    <p class="font-label text-label-sm text-on-surface-variant">Your Balance</p>
                </div>
            </div>

            {{-- Quick Actions --}}
            <section>
                <h3 class="font-headline text-headline-md text-on-surface mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach([
                        ['route' => 'trips.itinerary', 'icon' => 'event_note', 'label' => 'Itinerary', 'color' => 'primary'],
                        ['route' => 'trips.activities', 'icon' => 'history', 'label' => 'Activity', 'color' => 'secondary'],
                        ['route' => 'trips.voting', 'icon' => 'how_to_vote', 'label' => 'Voting', 'color' => 'secondary'],
                        ['route' => 'trips.finances', 'icon' => 'payments', 'label' => 'Finances', 'color' => 'tertiary'],
                        ['route' => 'trips.checklist', 'icon' => 'checklist', 'label' => 'Checklist', 'color' => 'primary'],
                        ['route' => 'trips.settlements', 'icon' => 'account_balance_wallet', 'label' => 'Settle Up', 'color' => 'secondary'],
                        ['route' => 'trips.documents', 'icon' => 'folder_open', 'label' => 'Documents', 'color' => 'primary'],
                        ['route' => 'trips.ai', 'icon' => 'auto_awesome', 'label' => 'WanderAI', 'color' => 'tertiary'],
                    ] as $action)
                    <a href="{{ route($action['route'], $trip) }}" class="card flex flex-col items-center text-center gap-3 hover:scale-[1.02] transition-transform cursor-pointer">
                        <div class="w-12 h-12 rounded-full bg-{{ $action['color'] }}/10 text-{{ $action['color'] }} flex items-center justify-center">
                            <span class="material-symbols-outlined">{{ $action['icon'] }}</span>
                        </div>
                        <span class="font-label text-label-md text-on-surface">{{ $action['label'] }}</span>
                    </a>
                    @endforeach
                </div>
            </section>

            {{-- Description --}}
            @if($trip->description)
            <section class="card">
                <h3 class="font-label text-label-md text-on-surface font-bold mb-2">About this Trip</h3>
                <p class="font-body text-body-md text-on-surface-variant">{{ $trip->description }}</p>
            </section>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="flex flex-col gap-stack-lg">

            {{-- Wander AI Smart Recommendations (Premium Design) --}}
            <section class="card bg-gradient-to-br from-primary/5 to-secondary/5 border-primary/10 overflow-hidden relative group">
                <div class="absolute top-0 right-0 w-24 h-24 bg-primary/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-[20px]">auto_awesome</span>
                    </div>
                    <div>
                        <h3 class="font-display text-label-md text-on-surface leading-none">Wander AI</h3>
                        <p class="font-body text-[10px] text-outline uppercase tracking-wider mt-1">Smart Recommendations</p>
                    </div>
                </div>

                <div class="bg-white/60 backdrop-blur-md rounded-2xl p-4 border border-white/50 shadow-sm relative z-10">
                    <p class="font-body text-body-md text-on-surface leading-relaxed mb-4 italic">
                        "Looks like a busy day! Don't forget to book <span class="text-primary font-bold">Taksi</span> for the airport transit at 08:00 AM."
                    </p>
                    <div class="flex gap-2">
                        <button class="flex-1 py-2 px-3 rounded-xl bg-surface-container-high text-on-surface font-label text-[11px] hover:bg-surface-container-highest transition-colors">Dismiss</button>
                        <button class="flex-1 py-2 px-3 rounded-xl bg-primary text-white font-label text-[11px] shadow-sm hover:bg-primary-hover transition-colors">Add to Itinerary</button>
                    </div>
                </div>
            </section>

            {{-- Route Area Map --}}
            <section class="card p-3 overflow-hidden">
                <div id="route-map" class="mb-3"></div>
                <div class="px-2 pb-1 flex justify-between items-center">
                    <div>
                        <h4 class="font-display text-label-md text-on-surface">Route Area</h4>
                        <p class="font-body text-[11px] text-outline">{{ $trip->itineraryDays()->withCount('items')->get()->sum('items_count') }} planned locations</p>
                    </div>
                    <button class="w-8 h-8 rounded-lg hover:bg-surface-container-low flex items-center justify-center text-outline transition-colors">
                        <span class="material-symbols-outlined text-[20px]">open_in_full</span>
                    </button>
                </div>
            </section>

            {{-- Members --}}
            <section class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-label text-label-md text-on-surface font-bold">Members ({{ $trip->members->count() }})</h3>
                    <button onclick="document.getElementById('invite-modal').classList.remove('hidden')" class="text-primary font-label text-label-sm flex items-center gap-1 hover:underline">
                        <span class="material-symbols-outlined text-[16px]">person_add</span> Invite
                    </button>
                </div>
                <div class="flex flex-col gap-3">
                    @foreach($trip->members as $member)
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant font-bold text-[12px]">{{ $member->initials }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-label text-label-md text-on-surface truncate">{{ $member->name }}</p>
                            <p class="font-label text-label-sm text-outline capitalize">{{ $member->pivot->role }}</p>
                        </div>
                        @if($member->id === $trip->created_by)
                        <span class="material-symbols-outlined text-tertiary-container text-[16px]" title="Trip Creator" style="font-variation-settings: 'FILL' 1;">star</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>

            {{-- Activity Feed --}}
            <section class="card">
                <h3 class="font-label text-label-md text-on-surface font-bold mb-3">Recent Activity</h3>
                <div class="relative pl-4 border-l border-surface-variant/50 flex flex-col gap-4">
                    @forelse($trip->activityLogs->take(8) as $log)
                    <div class="relative">
                        <span class="absolute -left-[21px] top-1 w-2.5 h-2.5 rounded-full bg-primary border-2 border-surface-container-lowest"></span>
                        <p class="font-body text-[13px] text-on-surface">
                            <span class="font-semibold">{{ $log->user->name }}</span>
                            {{ str_replace('_', ' ', $log->action) }}
                            @if($log->metadata && isset($log->metadata['title']))
                            <span class="text-primary">{{ $log->metadata['title'] }}</span>
                            @endif
                        </p>
                        <span class="font-label text-label-sm text-outline">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    @empty
                    <p class="font-body text-[13px] text-on-surface-variant">No activity yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    {{-- Invite Modal --}}
    <div id="invite-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-surface-container-lowest rounded-2xl shadow-elevation-3 w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-headline text-headline-md text-on-surface">Invite Friends</h3>
                <button onclick="this.closest('#invite-modal').classList.add('hidden')" class="text-outline hover:text-primary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="mb-6 pb-6 border-b border-surface-variant/30" x-data="{ 
                link: '{{ route('trips.join', $trip->getInviteCode()) }}',
                copied: false,
                copy() {
                    navigator.clipboard.writeText(this.link);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                }
            }">
                <label class="font-label text-label-md text-on-surface mb-2 block">Public Invite Link</label>
                <div class="flex gap-2">
                    <input readonly :value="link" class="input-field pl-4 flex-1 bg-surface-container text-outline text-label-sm" type="text">
                    <button @click="copy()" type="button" class="btn-secondary py-2 px-4 shrink-0">
                        <span class="material-symbols-outlined text-[18px]" x-text="copied ? 'check' : 'content_copy'"></span>
                    </button>
                </div>
                <p class="font-label text-label-sm text-outline mt-2">Bagikan link ini agar siapa saja bisa bergabung ke trip ini.</p>
            </div>

            <form method="POST" action="{{ route('trips.invite', $trip) }}" class="space-y-4">
                @csrf
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Email Addresses</label>
                    <textarea class="input-field pl-4 resize-none" name="emails" rows="3" placeholder="friend1@email.com, friend2@email.com" required></textarea>
                    <p class="font-label text-label-sm text-outline">Separate multiple emails with commas.</p>
                </div>
                <button type="submit" class="btn-primary w-full">
                    <span class="material-symbols-outlined">send</span> Send Invitations
                </button>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Map
            const map = L.map('route-map', {
                zoomControl: false,
                attributionControl: false
            }).setView([0, 0], 2);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            @php
                $mapLocations = $trip->itineraryDays->flatMap->items
                    ->whereNotNull('location_lat')
                    ->map(fn($item) => [
                        'lat' => (float) $item->location_lat,
                        'lng' => (float) $item->location_lng,
                        'title' => $item->title,
                        'type' => $item->type
                    ])->values();
            @endphp

            // Add Markers from Itinerary
            const locations = @json($mapLocations);

            if (locations.length > 0) {
                const markers = [];
                locations.forEach(loc => {
                    const marker = L.marker([loc.lat, loc.lng]).addTo(map);
                    marker.bindPopup(`<b>${loc.title}</b><br><small class="capitalize">${loc.type}</small>`);
                    markers.push([loc.lat, loc.lng]);
                });
                
                // Fit map to markers
                const bounds = L.latLngBounds(markers);
                map.fitBounds(bounds, { padding: [20, 20] });
            } else {
                // Fallback: Just show the destination center if possible (mocked)
                map.setView([-8.4095, 115.1889], 10); // Default to Bali if no coords
            }
        });
    </script>
</x-app-layout>
