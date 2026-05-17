<x-app-layout>
    @php $title = $trip->title . ' - Itinerary'; @endphp
    
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        #itinerary-map { height: 100%; width: 100%; border-radius: 12px; position: relative; z-index: 1 !important; }
        .leaflet-container { font-family: 'Inter', sans-serif; border-radius: 12px; z-index: 1 !important; }
        .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 1 !important; }
    </style>
    @endpush

    <div class="max-w-[1280px] mx-auto" x-data="{ modalOpen: false }">
        <div class="flex flex-col xl:flex-row gap-stack-lg">
            <div class="flex-1">

                {{-- Header Section (Bali Summer Break Style) --}}
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10">
                    <div>
                        <h1 class="font-display text-[40px] font-bold text-on-background leading-tight mb-2">{{ $trip->title }}</h1>
                        <div class="font-label text-[15px] text-on-surface-variant flex items-center gap-2">
                            Day {{ $selectedDay->day_number ?? 1 }} &bull; {{ $selectedDay->date?->format('F d, Y') ?? 'Planning Phase' }}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        @if($currentDay > 1)
                        <a href="?day={{ $currentDay - 1 }}" class="w-10 h-10 rounded-full border border-outline-variant flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </a>
                        @else
                        <div class="w-10 h-10 rounded-full border border-surface-variant flex items-center justify-center text-outline opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                        </div>
                        @endif
                        
                        @if($currentDay < $trip->itineraryDays->count())
                        <a href="?day={{ $currentDay + 1 }}" class="w-10 h-10 rounded-full border border-outline-variant flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                        @else
                        <div class="w-10 h-10 rounded-full border border-surface-variant flex items-center justify-center text-outline opacity-50 cursor-not-allowed">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{-- Timeline Container --}}
                <div class="relative pl-8 md:pl-10">
                    {{-- The Vertical Line --}}
                    <div class="absolute left-[11px] md:left-[15px] top-4 bottom-0 w-[2px] bg-primary/20 rounded-full"></div>

                    @if($selectedDay && $selectedDay->items->count() > 0)
                        @foreach($selectedDay->items as $item)
                        <div class="relative mb-8 last:mb-0 group animate-fade-in" style="animation-delay: {{ $loop->index * 100 }}ms">
                            {{-- Node --}}
                            @php
                                $nodeColor = $item->status === 'confirmed' ? 'bg-[#1e8f50]' : ($item->status === 'voting' ? 'bg-tertiary-container' : 'bg-primary');
                                $iconColor = $item->status === 'voting' ? 'text-on-tertiary-container' : ($item->status === 'confirmed' ? 'text-white' : 'text-on-primary');
                                $iconName = $item->type === 'flight' ? 'flight_takeoff' : ($item->type === 'hotel' ? 'hotel' : ($item->type === 'activity' ? 'local_activity' : 'place'));
                                if($item->status === 'voting') $iconName = 'how_to_vote';
                            @endphp
                            <div class="absolute -left-[22px] md:-left-[27px] top-4 w-6 h-6 rounded-full {{ $nodeColor }} flex items-center justify-center shadow-sm border-2 border-surface z-10 transition-transform group-hover:scale-110">
                                <span class="material-symbols-outlined {{ $iconColor }} text-[14px]">{{ $iconName }}</span>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-4">
                                {{-- Time Info --}}
                                <div class="sm:w-24 shrink-0 pt-1">
                                    <div class="font-label text-[14px] font-bold text-on-surface">
                                        {{ $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('h:i A') : '--:--' }}
                                    </div>
                                    @if($item->status === 'voting')
                                        <div class="font-label text-[12px] text-tertiary font-medium">Needs Vote</div>
                                    @else
                                        @if($item->end_time)
                                            <div class="font-label text-[12px] text-on-surface-variant">{{ \Carbon\Carbon::parse($item->start_time)->diffInHours(\Carbon\Carbon::parse($item->end_time)) }}h {{ \Carbon\Carbon::parse($item->start_time)->diffInMinutes(\Carbon\Carbon::parse($item->end_time)) % 60 }}m</div>
                                        @endif
                                    @endif
                                </div>

                                {{-- Variant 1: Voting Required --}}
                                @if($item->status === 'voting')
                                <div class="flex-1 bg-surface-bright rounded-xl p-5 shadow-sm border border-tertiary-container/30 border-dashed relative overflow-hidden group/card">
                                    <div class="absolute top-0 right-0 w-24 h-24 bg-tertiary-container/10 rounded-bl-full -mr-4 -mt-4"></div>
                                    <button onclick="document.getElementById('edit-modal-{{ $item->id }}').classList.remove('hidden')" class="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container transition-colors z-20">
                                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                    </button>
                                    <h3 class="font-headline text-[22px] font-bold text-on-surface mb-1 relative z-10">{{ $item->title }}</h3>
                                    <p class="font-body text-[14px] text-on-surface-variant mb-4 relative z-10">{{ $item->description ?? 'Choose what the group should do before dinner.' }}</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 relative z-10">
                                        <a href="{{ route('trips.voting', $trip) }}" class="text-left p-3 rounded-lg border border-surface-variant hover:border-primary hover:bg-primary/5 transition-colors bg-surface flex items-center justify-between group/btn">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-md bg-secondary-container/50 flex items-center justify-center shrink-0">
                                                    <span class="material-symbols-outlined text-on-secondary-container">how_to_vote</span>
                                                </div>
                                                <div>
                                                    <div class="font-label text-[14px] font-bold text-on-surface">Go to Poll</div>
                                                    <div class="font-label text-[12px] text-on-surface-variant">Vote on this activity</div>
                                                </div>
                                            </div>
                                            <span class="material-symbols-outlined text-outline group-hover/btn:text-primary transition-colors">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                                
                                {{-- Variant 2: Has Image --}}
                                @elseif($item->image_url)
                                <div class="flex-1 bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow border border-surface-container-high/50 flex flex-col md:flex-row relative group/card">
                                    <button onclick="document.getElementById('edit-modal-{{ $item->id }}').classList.remove('hidden')" class="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center bg-white/80 backdrop-blur-sm text-outline hover:text-on-surface hover:bg-surface-container transition-colors z-20">
                                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                    </button>
                                    <div class="h-32 md:h-auto md:w-48 shrink-0">
                                        <img src="{{ $item->image_url }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="p-5 flex-1 flex flex-col justify-center">
                                        <div class="flex justify-between items-start mb-2 pr-10">
                                            <div>
                                                @if($item->status === 'confirmed')
                                                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-primary/10 text-primary font-label text-[11px] mb-2 font-bold">
                                                    Confirmed
                                                </div>
                                                @endif
                                                <h3 class="font-headline text-[22px] font-bold text-on-surface">{{ $item->title }}</h3>
                                                <p class="font-body text-[14px] text-on-surface-variant">{{ $item->location ?? $item->description }}</p>
                                            </div>
                                            @if($item->location_lat && $item->location_lng)
                                            <div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant">
                                                <span class="material-symbols-outlined text-[18px]">map</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Variant 3: Standard Card --}}
                                @else
                                <div class="flex-1 bg-surface-container-lowest rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow border border-surface-container-high/50 relative group/card">
                                    <button onclick="document.getElementById('edit-modal-{{ $item->id }}').classList.remove('hidden')" class="absolute top-4 right-4 w-8 h-8 rounded-full flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                    </button>
                                    <div class="flex justify-between items-start mb-3 pr-10">
                                        <div>
                                            @if($item->status === 'confirmed')
                                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-primary/10 text-primary font-label text-[11px] mb-2 font-bold">
                                                Confirmed
                                            </div>
                                            @endif
                                            <h3 class="font-headline text-[22px] font-bold text-on-surface">{{ $item->title }}</h3>
                                            <p class="font-body text-[14px] text-on-surface-variant">{{ $item->description ?? $item->location }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center gap-3">
                                        <div class="flex -space-x-2">
                                            @foreach($trip->members->take(3) as $member)
                                            <div class="w-8 h-8 rounded-full bg-white border-2 border-white flex items-center justify-center shadow-sm overflow-hidden">
                                                <span class="text-[10px] font-bold text-primary">{{ $member->initials }}</span>
                                            </div>
                                            @endforeach
                                            @if($trip->members->count() > 3)
                                            <div class="inline-flex items-center justify-center h-8 w-8 rounded-full ring-2 ring-surface bg-surface-container text-on-surface-variant font-label text-[12px] font-bold">+{{ $trip->members->count() - 3 }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="ml-14 bg-white rounded-2xl p-12 border-2 border-dashed border-outline-variant flex flex-col items-center justify-center text-center shadow-sm">
                            <div class="w-16 h-16 rounded-full bg-primary/5 flex items-center justify-center mb-4">
                                <span class="material-symbols-outlined text-[32px] text-primary/40">explore</span>
                            </div>
                            <h4 class="font-headline text-headline-md text-on-surface mb-2">No activities planned yet</h4>
                            <p class="font-body text-body-md text-on-surface-variant mb-6 max-w-xs">Start building your itinerary to see it visualized on the timeline.</p>
                        </div>
                    @endif

                    {{-- Add Activity Trigger --}}
                    <button @click="modalOpen = true"
                            class="mt-stack-md ml-0 md:ml-24 flex items-center gap-2 text-primary font-label text-label-md hover:bg-surface-container py-2 px-4 rounded-full transition-colors border border-dashed border-primary/50">
                        <span class="material-symbols-outlined">add</span> Add Activity to Day {{ $currentDay }}
                    </button>
                </div>
            </div>

            {{-- Sidebar AI & Map --}}
            <aside class="w-full xl:w-80 shrink-0 flex flex-col gap-stack-md space-y-stack-md">
                
                {{-- Wander AI Assistant --}}
                <div class="bg-surface/60 backdrop-blur-xl rounded-2xl p-5 shadow-[0px_12px_40px_rgba(0,42,124,0.06)] border border-white/50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/20 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                    
                    <div class="flex items-center gap-3 mb-4 relative z-10">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-md">
                            <span class="material-symbols-outlined text-white">auto_awesome</span>
                        </div>
                        <div>
                            <h3 class="font-headline text-[18px] text-on-surface leading-tight font-bold">Wander AI</h3>
                            <p class="font-label text-label-sm text-on-surface-variant">Smart Recommendations</p>
                        </div>
                    </div>

                    <div class="bg-white/80 rounded-xl p-4 shadow-sm border border-surface-variant/30 relative z-10 mb-4">
                        <p class="font-body text-[14px] text-on-surface leading-relaxed mb-3">
                            "Looks like a busy day! Don't forget to book <span class="font-semibold text-primary">Taksi</span> for the airport transit at 08:00 AM."
                        </p>
                        <div class="flex gap-2">
                            <button class="flex-1 bg-surface-container hover:bg-surface-container-high text-on-surface text-label-sm font-label py-2 rounded-lg transition-colors text-center">Dismiss</button>
                            <button class="flex-1 bg-primary hover:bg-surface-tint text-on-primary text-label-sm font-label py-2 rounded-lg transition-colors text-center">Add to Itinerary</button>
                        </div>
                    </div>
                </div>

                {{-- Map Snippet --}}
                <div class="bg-white rounded-2xl p-4 shadow-[0px_4px_20px_rgba(0,0,0,0.04)] border border-surface-variant/30 flex flex-col h-48 relative overflow-hidden group" x-show="!modalOpen" x-cloak>
                    <div id="itinerary-map" class="absolute inset-0 w-full h-full z-0"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-surface/90 to-transparent pointer-events-none z-10"></div>
                    <div class="mt-auto relative z-20 flex justify-between items-end pb-2 px-2 pointer-events-none">
                        <div>
                            <h4 class="font-label text-label-md text-on-surface font-bold">Route Area</h4>
                            <p class="font-label text-[11px] text-on-surface-variant">{{ $selectedDay->items->count() }} planned locations</p>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm pointer-events-auto cursor-pointer hover:bg-surface-container transition-colors">
                            <span class="material-symbols-outlined text-primary text-[18px]">open_in_full</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('itinerary-map');
            if (!container) return;

            const map = L.map('itinerary-map').setView([-8.4095, 115.1889], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const items = @json($selectedDay->items->filter(fn($i) => $i->location_lat && $i->location_lng));
            const markers = [];

            items.forEach(item => {
                const marker = L.marker([item.location_lat, item.location_lng])
                    .addTo(map)
                    .bindPopup(`<b class="text-primary">${item.title}</b><br>${item.location || ''}`);
                markers.push(marker);
            });

            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        });
    </script>
    @endpush

    {{-- Add Activity Modal --}}
    @if($selectedDay)
    <div id="add-activity-modal" 
         x-show="modalOpen" 
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
         style="display: none;">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modalOpen = false"></div>
        
        {{-- Modal Content --}}
        <div class="relative bg-surface rounded-[32px] shadow-[0px_12px_40px_rgba(0,42,124,0.12)] border border-white/60 w-full max-w-lg p-8 z-[10000] overflow-hidden">
            {{-- Decorative Accent --}}
            <div class="absolute top-0 right-0 w-48 h-48 bg-primary/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
            
            <header class="flex justify-between items-center mb-8 relative z-10">
                <div>
                    <h3 class="font-headline text-headline-lg text-on-surface font-bold">Add Activity</h3>
                    <p class="font-label text-label-sm text-on-surface-variant mt-1">Plan a new event for Day {{ $selectedDay->day_number }}</p>
                </div>
                <button @click="modalOpen = false" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">close</span>
                </button>
            </header>
            
            <form method="POST" action="{{ route('trips.itinerary.store', [$trip, $selectedDay]) }}" class="space-y-5 relative z-10">
                @csrf
                <div>
                    <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Activity Title *</label>
                    <input class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-outline-variant" 
                           name="title" placeholder="e.g. Visit Tanah Lot Temple" required>
                </div>
                
                <div>
                    <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Description</label>
                    <textarea class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-outline-variant resize-none" 
                              name="description" rows="2" placeholder="Any special notes or links?"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Start Time</label>
                        <input class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" 
                               name="start_time" type="time">
                    </div>
                    <div>
                        <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Location</label>
                        <input class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-outline-variant" 
                               name="location" placeholder="Search places...">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Category</label>
                        <select class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" 
                                name="type">
                            <option value="activity">Activity</option>
                            <option value="restaurant">Dining</option>
                            <option value="transport">Transport</option>
                            <option value="flight">Flight</option>
                            <option value="hotel">Lodging</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Status</label>
                        <select class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" 
                                name="status">
                            <option value="confirmed">Confirmed</option>
                            <option value="tentative">Tentative</option>
                            <option value="voting">Needs Vote</option>
                        </select>
                    </div>
                </div>
                
                <div class="pt-2">
                    <button type="submit" class="w-full bg-primary text-on-primary font-label text-label-md py-4 rounded-full shadow-[0px_4px_20px_rgba(0,101,141,0.2)] hover:bg-surface-tint hover:shadow-[0px_8px_30px_rgba(0,101,141,0.3)] transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        Add to Itinerary
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
    {{-- Edit/Delete Activity Modals --}}
    @if($selectedDay && $selectedDay->items->count() > 0)
        @foreach($selectedDay->items as $item)
        <div id="edit-modal-{{ $item->id }}" 
             class="fixed inset-0 z-[9999] flex items-center justify-center p-4 hidden">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('edit-modal-{{ $item->id }}').classList.add('hidden')"></div>
            
            {{-- Modal Content --}}
            <div class="relative bg-surface rounded-[32px] shadow-[0px_12px_40px_rgba(0,42,124,0.12)] border border-white/60 w-full max-w-lg p-8 z-[10000] overflow-hidden">
                {{-- Decorative Accent --}}
                <div class="absolute top-0 right-0 w-48 h-48 bg-tertiary/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
                
                <header class="flex justify-between items-center mb-8 relative z-10">
                    <div>
                        <h3 class="font-headline text-headline-lg text-on-surface font-bold">Edit Activity</h3>
                        <p class="font-label text-label-sm text-on-surface-variant mt-1">Update details for this event</p>
                    </div>
                    <button onclick="document.getElementById('edit-modal-{{ $item->id }}').classList.add('hidden')" class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-surface-variant hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </header>
                
                <form method="POST" action="{{ route('trips.itinerary.update', [$trip, $item]) }}" class="space-y-5 relative z-10">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Activity Title *</label>
                        <input class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-outline-variant" 
                               name="title" value="{{ $item->title }}" required>
                    </div>
                    
                    <div>
                        <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Description</label>
                        <textarea class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-outline-variant resize-none" 
                                  name="description" rows="2">{{ $item->description }}</textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Start Time</label>
                            <input class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" 
                                   name="start_time" type="time" value="{{ $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '' }}">
                        </div>
                        <div>
                            <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Location</label>
                            <input class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all placeholder:text-outline-variant" 
                                   name="location" value="{{ $item->location }}">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Category</label>
                            <select class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" 
                                    name="type">
                                <option value="activity" {{ $item->type == 'activity' ? 'selected' : '' }}>Activity</option>
                                <option value="restaurant" {{ $item->type == 'restaurant' ? 'selected' : '' }}>Dining</option>
                                <option value="transport" {{ $item->type == 'transport' ? 'selected' : '' }}>Transport</option>
                                <option value="flight" {{ $item->type == 'flight' ? 'selected' : '' }}>Flight</option>
                                <option value="hotel" {{ $item->type == 'hotel' ? 'selected' : '' }}>Lodging</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-label text-[12px] text-on-surface-variant mb-1.5 ml-1">Status</label>
                            <select class="w-full bg-white border border-surface-variant/60 rounded-xl px-4 py-3 font-body text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-all" 
                                    name="status">
                                <option value="confirmed" {{ $item->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="tentative" {{ $item->status == 'tentative' ? 'selected' : '' }}>Tentative</option>
                                <option value="voting" {{ $item->status == 'voting' ? 'selected' : '' }}>Needs Vote</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="pt-2 flex gap-3">
                        <button type="submit" class="flex-1 bg-primary text-on-primary font-label text-label-md py-4 rounded-full shadow-[0px_4px_20px_rgba(0,101,141,0.2)] hover:bg-surface-tint hover:shadow-[0px_8px_30px_rgba(0,101,141,0.3)] transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            Save Changes
                        </button>
                </form>
                        
                <form method="POST" action="{{ route('trips.itinerary.destroy', [$trip, $item]) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Are you sure you want to delete this activity?')" class="h-full px-6 bg-error/10 text-error font-label text-label-md rounded-full hover:bg-error hover:text-white transition-all flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </button>
                </form>
                    </div>
            </div>
        </div>
        @endforeach
    @endif
    </div>
</x-app-layout>
