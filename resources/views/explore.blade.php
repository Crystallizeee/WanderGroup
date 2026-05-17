<x-app-layout>
    @php $title = 'Explore Destinations'; @endphp

    {{-- Hero --}}
    <div class="relative rounded-2xl overflow-hidden mb-stack-lg bg-gradient-to-br from-primary to-secondary p-8 md:p-12">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl -mr-24 -mt-24 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-black/10 rounded-full blur-3xl -ml-16 -mb-16 pointer-events-none"></div>
        <div class="relative z-10 max-w-2xl">
            <h1 class="font-display text-display-lg text-white mb-3">Explore Destinations</h1>
            <p class="font-body text-body-lg text-white/80 mb-6">Discover inspiring places and start planning your next group adventure.</p>
            <div class="relative max-w-lg">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
                <input type="text" id="search-destinations" placeholder="Search destinations, activities..."
                       class="w-full pl-12 pr-4 py-3.5 rounded-xl bg-white/95 text-on-surface font-body text-body-md shadow-elevation-2 outline-none focus:ring-2 focus:ring-white/50 transition-all"
                       oninput="filterDestinations(this.value)">
            </div>
        </div>
    </div>

    {{-- Category Chips --}}
    <div class="flex gap-3 mb-stack-lg overflow-x-auto hide-scrollbar pb-2">
        <button class="chip bg-primary text-on-primary px-5 py-2 text-label-md font-bold" onclick="filterDestinations('')">All</button>
        @foreach($categories as $cat)
        <button class="chip bg-surface-container-low text-on-surface-variant hover:bg-surface-container px-4 py-2 text-label-md"
                onclick="filterDestinations('{{ $cat['name'] }}')">
            <span class="material-symbols-outlined text-{{ $cat['color'] }} text-[18px]">{{ $cat['icon'] }}</span>
            {{ $cat['name'] }}
        </button>
        @endforeach
    </div>

    {{-- Destination Cards Grid --}}
    <div id="destination-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-stack-lg">
        @foreach($destinations as $dest)
        <div class="destination-card group rounded-2xl overflow-hidden shadow-elevation-1 hover:shadow-elevation-3 transition-all duration-300 hover:-translate-y-1 bg-surface-container-lowest"
             data-tags="{{ implode(',', $dest['tags']) }}" data-name="{{ strtolower($dest['name']) }}">
            {{-- Gradient Header --}}
            <div class="h-40 bg-gradient-to-br {{ $dest['gradient'] }} relative flex items-end p-5">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                <span class="absolute top-4 right-4 text-[48px] opacity-30 group-hover:opacity-50 transition-opacity">{{ $dest['emoji'] }}</span>
                <div>
                    <h3 class="font-headline text-headline-md text-white drop-shadow-sm">{{ $dest['name'] }}</h3>
                    <p class="font-label text-label-sm text-white/80">{{ $dest['tagline'] }}</p>
                </div>
            </div>

            {{-- Content --}}
            <div class="p-5">
                <p class="font-body text-body-md text-on-surface-variant mb-4 line-clamp-2">{{ $dest['description'] }}</p>

                {{-- Tags --}}
                <div class="flex flex-wrap gap-1.5 mb-4">
                    @foreach($dest['tags'] as $tag)
                    <span class="chip bg-surface-container text-on-surface-variant text-[11px] px-2 py-0.5">{{ $tag }}</span>
                    @endforeach
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-3 pt-3 border-t border-surface-variant/50">
                    <div>
                        <div class="font-label text-label-sm text-outline">Avg Budget</div>
                        <div class="font-label text-label-md text-on-surface">{{ rupiah($dest['avg_budget']) }}</div>
                    </div>
                    <div>
                        <div class="font-label text-label-sm text-outline">Best Time</div>
                        <div class="font-label text-label-md text-on-surface text-[12px]">{{ $dest['best_months'] }}</div>
                    </div>
                    <div>
                        <div class="font-label text-label-sm text-outline">Trips</div>
                        <div class="font-label text-label-md text-primary">{{ number_format($dest['trips_planned']) }}</div>
                    </div>
                </div>

                {{-- CTA --}}
                <a href="{{ route('trips.create') }}?destination={{ urlencode($dest['name']) }}"
                   class="btn-primary w-full mt-4 py-2.5 text-label-sm">
                    <span class="material-symbols-outlined text-[16px]">flight_takeoff</span>
                    Plan a Trip Here
                </a>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter Script --}}
    <script>
        function filterDestinations(query) {
            query = query.toLowerCase();
            document.querySelectorAll('.destination-card').forEach(card => {
                const name = card.dataset.name || '';
                const tags = (card.dataset.tags || '').toLowerCase();
                const matches = !query || name.includes(query) || tags.includes(query);
                card.style.display = matches ? '' : 'none';
                card.style.opacity = matches ? '1' : '0';
            });
        }
    </script>
</x-app-layout>
