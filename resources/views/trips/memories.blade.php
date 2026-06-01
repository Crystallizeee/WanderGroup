<x-app-layout>
    @php $title = $trip->title . ' - Memories'; @endphp

    <div x-data="{
        activePhoto: null,
        activeIndex: 0,
        photos: [],
        filterTag: 'all',
        filterUploader: 'all',
        showHighlightedOnly: false,

        init() {
            this.photos = Array.from(document.querySelectorAll('[data-memory-card]')).map(el => JSON.parse(el.dataset.memoryCard));
        },

        openLightbox(photo, index) {
            this.activePhoto = photo;
            this.activeIndex = index;
            document.body.style.overflow = 'hidden';
        },

        closeLightbox() {
            this.activePhoto = null;
            document.body.style.overflow = '';
        },

        prevPhoto() {
            if (this.activeIndex > 0) {
                this.activeIndex--;
                this.activePhoto = this.visiblePhotos[this.activeIndex];
            }
        },

        nextPhoto() {
            if (this.activeIndex < this.visiblePhotos.length - 1) {
                this.activeIndex++;
                this.activePhoto = this.visiblePhotos[this.activeIndex];
            }
        },

        get visiblePhotos() {
            return this.photos.filter(p => {
                if (this.showHighlightedOnly && !p.is_highlighted) return false;
                if (this.filterTag !== 'all' && !(p.tags || []).includes(this.filterTag)) return false;
                if (this.filterUploader !== 'all' && p.uploader_id != this.filterUploader) return false;
                return true;
            });
        }
    }"
    @keydown.escape.window="closeLightbox()"
    @keydown.arrow-left.window="prevPhoto()"
    @keydown.arrow-right.window="nextPhoto()">

    {{-- ══════════════════════════════════════════════════
         HEADER  
    ══════════════════════════════════════════════════ --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="font-headline-lg-mobile md:font-display-lg text-headline-lg-mobile md:text-display-lg text-on-surface flex items-center gap-3">
                <span class="material-symbols-outlined text-primary text-[32px]">photo_library</span>
                Trip Memories
            </h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Capture and share your best moments — auto-tagged by AI</p>
        </div>
        <button onclick="document.getElementById('upload-memory-modal').classList.remove('hidden')"
                class="flex-shrink-0 flex items-center gap-2 bg-primary text-on-primary py-2.5 px-5 rounded-full font-label-md text-label-md hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 shadow-sm">
            <span class="material-symbols-outlined text-[20px]">add_photo_alternate</span>
            Share a Photo
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════
         STATS BAR
    ══════════════════════════════════════════════════ --}}
    @if($stats['total'] > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        @php
            $statsItems = [
                ['icon' => 'photo_library', 'label' => 'Photos', 'value' => $stats['total'], 'color' => 'text-primary', 'bg' => 'bg-primary/10'],
                ['icon' => 'group', 'label' => 'Contributors', 'value' => $stats['members'], 'color' => 'text-secondary', 'bg' => 'bg-secondary/10'],
                ['icon' => 'star', 'label' => 'Highlights', 'value' => $stats['highlighted'], 'color' => 'text-amber-500', 'bg' => 'bg-amber-500/10'],
                ['icon' => 'location_on', 'label' => 'Locations', 'value' => $stats['locations'], 'color' => 'text-tertiary', 'bg' => 'bg-tertiary/10'],
            ];
        @endphp
        @foreach($statsItems as $s)
        <div class="bg-surface-container-lowest border border-surface-variant/30 rounded-2xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined {{ $s['color'] }} text-[20px]">{{ $s['icon'] }}</span>
            </div>
            <div>
                <p class="font-headline text-headline-md text-on-surface leading-none">{{ $s['value'] }}</p>
                <p class="font-body text-[11px] text-on-surface-variant mt-0.5">{{ $s['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         HIGHLIGHTED HERO (if any)
    ══════════════════════════════════════════════════ --}}
    @php $highlighted = $memories->where('is_highlighted', true); @endphp
    @if($highlighted->count() > 0)
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-amber-500 text-[20px]">star</span>
            <h3 class="font-label text-label-lg text-on-surface font-semibold uppercase tracking-wider text-sm">✨ Featured Memories</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($highlighted->take(3) as $i => $memory)
            @php
                $isAnalyzing = $memory->location === 'Menganalisis lokasi...';
                $canDelete = ($memory->user_id === auth()->id() || $trip->organizers->contains(auth()->user()));
                $canHighlight = ($memory->user_id === auth()->id() || $trip->organizers->contains(auth()->user()));
                $photoIndex = $memories->search(fn($m) => $m->id === $memory->id);
                $photoData = json_encode([
                    'id' => $memory->id,
                    'url' => $memory->image_url,
                    'caption' => $memory->caption ?? '',
                    'location' => $memory->location ?? '',
                    'is_analyzing' => $isAnalyzing,
                    'uploader' => $memory->user->name,
                    'uploader_id' => $memory->user_id,
                    'uploader_initials' => $memory->user->initials,
                    'date' => $memory->taken_at ? $memory->taken_at->format('M d, Y H:i') : $memory->created_at->format('M d, Y'),
                    'tags' => $memory->ai_tags ?? [],
                    'is_highlighted' => true,
                    'is_video' => (bool)$memory->is_video,
                    'can_delete' => $canDelete,
                    'can_highlight' => $canHighlight,
                    'delete_url' => route('trips.memories.destroy', [$trip, $memory]),
                    'highlight_url' => route('trips.memories.highlight', [$trip, $memory]),
                ]);
            @endphp
            <div class="relative rounded-2xl overflow-hidden group cursor-pointer {{ $i === 0 ? 'md:col-span-2 md:row-span-2' : '' }} aspect-video"
                 @click="openLightbox({{ $photoData }}, {{ $photoIndex }})"
                 data-memory-card='{{ $photoData }}'>
                @if($memory->is_video)
                <video src="{{ $memory->image_url }}" alt="{{ $memory->caption ?? 'Featured Memory' }}"
                       class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                       muted playsinline loop preload="metadata"
                       onmouseenter="this.play()" onmouseleave="this.pause(); this.currentTime = 0;">
                </video>
                <div class="absolute top-3 right-3 z-20 w-6 h-6 bg-black/50 rounded-full flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-white text-[14px]">play_arrow</span>
                </div>
                @else
                <img src="{{ $memory->image_url }}" alt="{{ $memory->caption ?? 'Featured Memory' }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                @endif
                
                {{-- Golden Highlight Badge --}}
                <div class="absolute top-3 left-3 flex items-center gap-1 bg-amber-500 text-white text-[11px] font-label font-bold px-2.5 py-1 rounded-full shadow-lg">
                    <span class="material-symbols-outlined text-[14px]">star</span>
                    Featured
                </div>

                {{-- Gradient overlay --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4 text-white">
                    @if($memory->caption)
                        <p class="font-headline text-label-lg font-bold mb-1 line-clamp-2">{{ $memory->caption }}</p>
                    @endif
                    @if($memory->location && !$isAnalyzing)
                        <div class="flex items-center gap-1 text-[12px] text-white/80">
                            <span class="material-symbols-outlined text-[14px]">location_on</span>
                            <span>{{ $memory->location }}</span>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         FILTERS BAR
    ══════════════════════════════════════════════════ --}}
    @if($memories->count() > 0)
    <div class="flex flex-wrap items-center gap-2 mb-6 p-3 bg-surface-container-lowest border border-surface-variant/30 rounded-2xl">
        <span class="font-label text-label-sm text-on-surface-variant mr-1">Filter:</span>

        {{-- Highlighted Toggle --}}
        <button @click="showHighlightedOnly = !showHighlightedOnly"
                :class="showHighlightedOnly ? 'bg-amber-500 text-white border-amber-500' : 'bg-transparent text-on-surface-variant border-surface-variant/50 hover:border-amber-500 hover:text-amber-500'"
                class="flex items-center gap-1 text-[12px] font-label px-3 py-1.5 rounded-full border transition-all duration-200">
            <span class="material-symbols-outlined text-[14px]">star</span>
            Highlighted
        </button>

        {{-- Tag Filters --}}
        @php $allTags = $memories->flatMap(fn($m) => $m->ai_tags ?? [])->unique()->take(8); @endphp
        @foreach($allTags as $tag)
        <button @click="filterTag = filterTag === '{{ $tag }}' ? 'all' : '{{ $tag }}'"
                :class="filterTag === '{{ $tag }}' ? 'bg-primary text-on-primary border-primary' : 'bg-transparent text-on-surface-variant border-surface-variant/50 hover:border-primary hover:text-primary'"
                class="flex items-center gap-1 text-[12px] font-label px-3 py-1.5 rounded-full border transition-all duration-200">
            #{{ $tag }}
        </button>
        @endforeach

        {{-- Uploader Filters --}}
        @foreach($memories->groupBy('user_id') as $userId => $userMemories)
        @php $firstMemory = $userMemories->first(); @endphp
        <button @click="filterUploader = filterUploader == '{{ $userId }}' ? 'all' : '{{ $userId }}'"
                :class="filterUploader == '{{ $userId }}' ? 'bg-secondary text-on-secondary border-secondary' : 'bg-transparent text-on-surface-variant border-surface-variant/50 hover:border-secondary hover:text-secondary'"
                class="flex items-center gap-1.5 text-[12px] font-label px-3 py-1.5 rounded-full border transition-all duration-200">
            <span class="w-4 h-4 rounded-full bg-primary/20 text-primary font-bold text-[9px] flex items-center justify-center">{{ $firstMemory->user->initials }}</span>
            {{ $firstMemory->user->name }}
        </button>
        @endforeach

        {{-- Clear Filters --}}
        <button @click="filterTag = 'all'; filterUploader = 'all'; showHighlightedOnly = false"
                x-show="filterTag !== 'all' || filterUploader !== 'all' || showHighlightedOnly"
                class="ml-auto text-[12px] font-label text-outline hover:text-on-surface flex items-center gap-1 transition-colors">
            <span class="material-symbols-outlined text-[14px]">close</span>
            Clear
        </button>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         MAIN MASONRY GALLERY
    ══════════════════════════════════════════════════ --}}
    @if($memories->isEmpty())
        <div class="bg-surface-bright rounded-2xl border-2 border-dashed border-outline-variant p-16 flex flex-col items-center justify-center text-center">
            <span class="material-symbols-outlined text-[56px] text-primary/25 mb-4">photo_library</span>
            <h4 class="font-headline text-headline-md text-on-surface mb-2">No memories shared yet</h4>
            <p class="font-body text-body-md text-on-surface-variant mb-6 max-w-sm">Be the first to capture and share a beautiful snapshot from your trip! AI will auto-tag and detect locations.</p>
            <button onclick="document.getElementById('upload-memory-modal').classList.remove('hidden')" class="btn-primary">
                <span class="material-symbols-outlined">add_photo_alternate</span> Upload Your First Photo
            </button>
        </div>
    @else
        {{-- All Photos Heading --}}
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-[13px] font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px]" style="color:var(--md-sys-color-primary,#4f6df5)">grid_view</span>
                All Photos
                <span class="font-normal normal-case text-[12px] text-gray-400">({{ $memories->count() }})</span>
            </h3>
        </div>

        {{-- ── Photo Grid (3 cols mobile · 4 cols md · 5 cols lg) ── --}}
        <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-1.5 md:gap-2">
            @foreach($memories as $i => $memory)
            @php
                $isAnalyzing = $memory->location === 'Menganalisis lokasi...';
                $canDelete   = ($memory->user_id === auth()->id() || $trip->organizers->contains(auth()->user()));
                $canHighlight= ($memory->user_id === auth()->id() || $trip->organizers->contains(auth()->user()));
                $photoData   = json_encode([
                    'id'               => $memory->id,
                    'url'              => $memory->image_url,
                    'caption'          => $memory->caption ?? '',
                    'location'         => $memory->location ?? '',
                    'is_analyzing'     => $isAnalyzing,
                    'uploader'         => $memory->user->name,
                    'uploader_id'      => $memory->user_id,
                    'uploader_initials'=> $memory->user->initials,
                    'date'             => $memory->taken_at ? $memory->taken_at->format('M d, Y H:i') : $memory->created_at->format('M d, Y'),
                    'tags'             => $memory->ai_tags ?? [],
                    'is_highlighted'   => (bool)$memory->is_highlighted,
                    'is_video'         => (bool)$memory->is_video,
                    'can_delete'       => $canDelete,
                    'can_highlight'    => $canHighlight,
                    'delete_url'       => route('trips.memories.destroy', [$trip, $memory]),
                    'highlight_url'    => route('trips.memories.highlight', [$trip, $memory]),
                ]);
            @endphp

            {{-- Each tile: square aspect ratio, hover reveals overlay --}}
            <div class="relative group cursor-pointer rounded-lg overflow-hidden bg-gray-100
                        {{ $memory->is_highlighted ? 'ring-2 ring-amber-400 ring-offset-1' : '' }}"
                 style="aspect-ratio: 1 / 1;"
                 x-show="
                    (filterTag === 'all' || {{ json_encode($memory->ai_tags ?? []) }}.includes(filterTag)) &&
                    (filterUploader === 'all' || filterUploader == '{{ $memory->user_id }}') &&
                    (!showHighlightedOnly || {{ $memory->is_highlighted ? 'true' : 'false' }})
                 "
                 data-memory-card='{{ $photoData }}'
                 @click="openLightbox({{ $photoData }}, {{ $i }})">

                {{-- Highlight star badge --}}
                @if($memory->is_highlighted)
                <div class="absolute top-1.5 left-1.5 z-20 w-5 h-5 bg-amber-500 rounded-full flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-white text-[11px]">star</span>
                </div>
                @endif

                @if($memory->is_video)
                {{-- Video Thumbnail: mute, playsinline, loop, play on hover --}}
                <video src="{{ $memory->image_url }}"
                       class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 ease-out"
                       muted playsinline loop preload="metadata"
                       onmouseenter="this.play()" onmouseleave="this.pause(); this.currentTime = 0;">
                </video>
                <div class="absolute top-1.5 right-1.5 z-20 w-5 h-5 bg-black/50 rounded-full flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-white text-[12px]">play_arrow</span>
                </div>
                @else
                {{-- Photo thumbnail --}}
                <img src="{{ $memory->image_url }}"
                     alt="{{ $memory->caption ?? 'Trip Memory' }}"
                     loading="lazy"
                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 ease-out">
                @endif

                {{-- Hover overlay --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent
                            opacity-0 group-hover:opacity-100 transition-opacity duration-200
                            flex flex-col justify-end p-2 text-white">

                    {{-- Caption --}}
                    @if($memory->caption)
                    <p class="text-[10px] font-semibold leading-tight line-clamp-2 mb-1">{{ $memory->caption }}</p>
                    @endif

                    {{-- Location --}}
                    <div class="flex items-center gap-0.5 text-[9px] text-white/80 mb-1.5">
                        @if($isAnalyzing)
                            <svg class="animate-spin h-2.5 w-2.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span class="animate-pulse ml-1">Analyzing…</span>
                        @else
                            <span class="material-symbols-outlined text-[10px]">location_on</span>
                            <span class="line-clamp-1">{{ $memory->location ?? 'Unknown' }}</span>
                        @endif
                    </div>

                    {{-- Bottom: avatar + action buttons (pointer-events-auto) --}}
                    <div class="flex items-center justify-between pointer-events-auto">
                        <div class="flex items-center gap-1">
                            <div class="w-4 h-4 rounded-full bg-white/30 flex items-center justify-center text-[8px] font-bold uppercase">
                                {{ $memory->user->initials }}
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            {{-- Highlight --}}
                            @if($canHighlight)
                            <form action="{{ route('trips.memories.highlight', [$trip, $memory]) }}" method="POST" class="inline" @click.stop>
                                @csrf
                                <button type="submit"
                                        class="w-5 h-5 rounded-full flex items-center justify-center transition-colors
                                               {{ $memory->is_highlighted ? 'bg-amber-500 text-white' : 'bg-black/30 hover:bg-amber-500 text-white' }}"
                                        title="{{ $memory->is_highlighted ? 'Unfeature' : 'Feature' }}">
                                    <span class="material-symbols-outlined text-[11px]">star</span>
                                </button>
                            </form>
                            @endif

                            {{-- Delete --}}
                            @if($canDelete)
                            <form action="{{ route('trips.memories.destroy', [$trip, $memory]) }}" method="POST" class="inline" @click.stop
                                  onsubmit="return confirm('Delete this photo?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-5 h-5 rounded-full bg-black/30 hover:bg-red-600 text-white flex items-center justify-center transition-colors"
                                        title="Delete">
                                    <span class="material-symbols-outlined text-[11px]">delete</span>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Empty state for active filters --}}
        <div x-show="visiblePhotos.length === 0" style="display: none;" class="py-16 text-center">
            <span class="material-symbols-outlined text-[48px] text-gray-300 mb-3 block">search_off</span>
            <p class="text-gray-500 text-sm">No photos match your filter.</p>
            <button @click="filterTag = 'all'; filterUploader = 'all'; showHighlightedOnly = false" class="mt-3 text-blue-500 text-sm hover:underline">Clear filters</button>
        </div>
    @endif



    {{-- ══════════════════════════════════════════════════
         LIGHTBOX MODAL
    ══════════════════════════════════════════════════ --}}
    <div x-show="activePhoto"
         style="display: none;"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 backdrop-blur-sm">

        {{-- Clickable backdrop --}}
        <div class="absolute inset-0 cursor-pointer" @click="closeLightbox()"></div>

        {{-- Prev/Next Arrows --}}
        <button @click.stop="prevPhoto()" x-show="activeIndex > 0"
                class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/10 hover:bg-white/25 rounded-full flex items-center justify-center text-white transition-all backdrop-blur-sm">
            <span class="material-symbols-outlined">chevron_left</span>
        </button>
        <button @click.stop="nextPhoto()" x-show="activeIndex < photos.length - 1"
                class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white/10 hover:bg-white/25 rounded-full flex items-center justify-center text-white transition-all backdrop-blur-sm">
            <span class="material-symbols-outlined">chevron_right</span>
        </button>

        {{-- Lightbox Panel --}}
        <div class="relative z-10 max-w-5xl w-full mx-4 flex flex-col md:flex-row bg-white dark:bg-zinc-900 rounded-3xl overflow-hidden shadow-2xl border border-white/10 max-h-[90vh]"
             @click.stop>

            {{-- Close --}}
            <button @click="closeLightbox()" class="absolute top-4 right-4 z-30 w-9 h-9 rounded-full bg-black/50 hover:bg-black/80 text-white flex items-center justify-center transition-colors shadow-md">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>

            {{-- Left: Image/Video --}}
            <div class="flex-1 bg-black flex items-center justify-center min-h-[280px] md:min-h-0 overflow-hidden">
                <template x-if="activePhoto && activePhoto.is_video">
                    <video :src="activePhoto.url" controls autoplay class="max-w-full max-h-[55vh] md:max-h-[85vh] object-contain"></video>
                </template>
                <template x-if="activePhoto && !activePhoto.is_video">
                    <img :src="activePhoto.url"
                         :alt="activePhoto.caption"
                         class="max-w-full max-h-[55vh] md:max-h-[85vh] object-contain">
                </template>
            </div>

            {{-- Right: Details Sidebar --}}
            <div class="w-full md:w-80 flex-shrink-0 bg-white dark:bg-zinc-900 p-6 flex flex-col gap-5 border-t md:border-t-0 md:border-l border-gray-200 dark:border-zinc-700 overflow-y-auto max-h-[45vh] md:max-h-none">

                {{-- Highlighted badge --}}
                <template x-if="activePhoto && activePhoto.is_highlighted">
                    <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                        <span class="material-symbols-outlined text-amber-500 text-[18px]">star</span>
                        <span class="text-[12px] text-amber-700 font-semibold">Featured Memory</span>
                    </div>
                </template>

                {{-- Uploader --}}
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center uppercase text-sm flex-shrink-0"
                         x-text="activePhoto ? activePhoto.uploader_initials : ''"></div>
                    <div>
                        <p class="text-[14px] font-semibold text-gray-900" x-text="activePhoto ? activePhoto.uploader : ''"></p>
                        <p class="text-[11px] text-gray-500" x-text="activePhoto ? activePhoto.date : ''"></p>
                    </div>
                </div>

                {{-- Caption --}}
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Caption</h4>
                    <p class="text-[14px] text-gray-700 leading-relaxed"
                       x-text="activePhoto && activePhoto.caption ? activePhoto.caption : 'No caption provided.'"
                       :class="activePhoto && !activePhoto.caption ? 'italic text-gray-400' : ''"></p>
                </div>

                {{-- Location --}}
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Location</h4>
                    <template x-if="activePhoto && activePhoto.is_analyzing">
                        <div class="flex items-center gap-2 text-blue-500 text-sm animate-pulse">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span>AI analyzing...</span>
                        </div>
                    </template>
                    <template x-if="activePhoto && !activePhoto.is_analyzing">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-blue-500">location_on</span>
                            <span class="text-[14px] text-gray-700" x-text="activePhoto.location || 'Location not detected'"></span>
                        </div>
                    </template>
                </div>

                {{-- AI Tags --}}
                <div x-show="activePhoto && activePhoto.tags && activePhoto.tags.length > 0">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">AI Tags</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="tag in (activePhoto ? activePhoto.tags : [])">
                            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 text-[11px] font-semibold uppercase tracking-wide" x-text="'#' + tag"></span>
                        </template>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-auto pt-4 border-t border-gray-100 flex flex-col gap-2">
                    <a :href="activePhoto ? activePhoto.url : '#'"
                       target="_blank"
                       rel="noopener"
                       class="py-2 px-4 text-[13px] font-medium border border-gray-200 text-gray-700 rounded-xl flex items-center justify-center gap-2 hover:bg-gray-50 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">download</span>
                        Download Photo
                    </a>

                    {{-- Highlight toggle in lightbox --}}
                    <template x-if="activePhoto && activePhoto.can_highlight">
                        <form :action="activePhoto.highlight_url" method="POST">
                            @csrf
                            <button type="submit"
                                    :class="activePhoto.is_highlighted ? 'bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100' : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
                                    class="w-full py-2 px-4 text-[13px] font-medium flex items-center justify-center gap-2 rounded-xl border transition-all duration-200">
                                <span class="material-symbols-outlined text-[16px]">star</span>
                                <span x-text="activePhoto.is_highlighted ? 'Remove from Featured' : 'Feature this Photo'"></span>
                            </button>
                        </form>
                    </template>

                    <template x-if="activePhoto && activePhoto.can_delete">
                        <form :action="activePhoto.delete_url" method="POST" onsubmit="return confirm('Delete this photo?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full py-2 px-4 text-[13px] font-medium bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 rounded-xl flex items-center justify-center gap-2 transition-all duration-200">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                Delete Photo
                            </button>
                        </form>
                    </template>
                </div>
            </div>
        </div>

        {{-- Photo counter --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 bg-black/60 text-white/80 text-[12px] font-label px-4 py-1.5 rounded-full backdrop-blur-sm"
             x-text="(activeIndex + 1) + ' / ' + photos.length">
        </div>
    </div>

    </div>{{-- End alpine x-data --}}

    {{-- ══════════════════════════════════════════════════
         UPLOAD MODAL
    ══════════════════════════════════════════════════ --}}
    <div id="upload-memory-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 border border-gray-100 flex flex-col max-h-[85vh] md:max-h-[90vh]">
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <div>
                    <h3 class="text-[18px] font-bold text-gray-900">Share memories</h3>
                    <p class="text-[12px] text-gray-500 mt-0.5">EXIF & AI will auto-detect details</p>
                </div>
                <button onclick="document.getElementById('upload-memory-modal').classList.add('hidden')" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors">
                    <span class="material-symbols-outlined text-gray-400 text-[20px]">close</span>
                </button>
            </div>

            <form method="POST" action="{{ route('trips.memories.store', $trip) }}" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden"
                  x-data="{
                    files: [],
                    fileSelected(e) {
                        const rawFiles = Array.from(e.target.files);
                        this.files = rawFiles.map((file, index) => ({
                            name: file.name,
                            url: index < 12 ? URL.createObjectURL(file) : null
                        }));
                    }
                  }">
                @csrf

                {{-- Scrollable Form Fields --}}
                <div class="flex-1 overflow-y-auto space-y-4 pr-1 mb-4">
                    {{-- Drop Zone --}}
                    <div>
                        <label class="font-label text-label-md text-on-surface block mb-2">Photos & Videos *</label>
                        <div class="border-2 border-dashed border-outline-variant hover:border-primary/60 rounded-2xl relative p-6 flex flex-col items-center justify-center text-center cursor-pointer transition-all bg-surface-bright group min-h-[160px]">
                            <input class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" name="images[]" type="file" accept="image/*,video/*" required multiple @change="fileSelected">

                            <div x-show="files.length === 0" class="flex flex-col items-center gap-2">
                                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center mb-1 group-hover:bg-primary/20 transition-colors">
                                    <span class="material-symbols-outlined text-[28px] text-primary">add_a_photo</span>
                                </div>
                                <span class="font-label text-label-md text-on-surface">Click or Drag Files Here</span>
                                <span class="text-[11px] text-outline">JPEG, PNG, MP4, MOV • Max 50MB each • Upload multiple!</span>
                            </div>

                            {{-- Grid Preview for <= 12 files --}}
                            <div x-show="files.length > 0 && files.length <= 12" style="display: none;" class="w-full flex flex-col items-center gap-3">
                                <div class="flex flex-wrap gap-2 justify-center p-1">
                                    <template x-for="file in files">
                                        <div class="w-16 h-16 rounded-lg overflow-hidden border border-primary/20 shadow-sm bg-surface-dim relative">
                                            <template x-if="file.name.match(/\.(mp4|mov|avi|webm)$/i)">
                                                <video :src="file.url" class="w-full h-full object-cover" muted playsinline></video>
                                            </template>
                                            <template x-if="!file.name.match(/\.(mp4|mov|avi|webm)$/i)">
                                                <img :src="file.url" class="w-full h-full object-cover">
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <span class="font-label text-label-sm text-primary font-semibold" x-text="files.length + ' file(s) selected'"></span>
                            </div>

                            {{-- Bento summary list for > 12 files --}}
                            <div x-show="files.length > 12" style="display: none;" class="w-full flex flex-col items-center gap-3">
                                <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-[32px] text-primary">folder_open</span>
                                </div>
                                <div class="text-center">
                                    <span class="font-headline text-label-lg font-bold text-on-surface block" x-text="files.length + ' Photos Selected'"></span>
                                    <span class="text-[11px] text-outline block mt-0.5">High-performance upload mode active</span>
                                </div>
                                {{-- Show a small list of first few file names --}}
                                <div class="bg-surface-bright/50 border border-outline-variant/30 rounded-xl p-3 w-full max-w-xs text-left text-[11px] text-on-surface-variant font-mono space-y-1">
                                    <template x-for="(file, index) in files.slice(0, 4)">
                                        <div class="truncate flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[12px] text-outline">image</span>
                                            <span x-text="file.name"></span>
                                        </div>
                                    </template>
                                    <div class="text-outline italic text-center pt-1" x-show="files.length > 4" x-text="'+ ' + (files.length - 4) + ' more photos...'"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Caption --}}
                    <div>
                        <label class="font-label text-label-md text-on-surface block mb-2">Caption <span class="text-outline font-normal">(Optional)</span></label>
                        <input type="text" name="caption" class="input-field pl-4 py-2.5 w-full" placeholder="e.g. Stunning sunset from the clifftop!">
                    </div>
                </div>

                {{-- Fixed Bottom Buttons --}}
                <div class="pt-4 border-t border-surface-variant/30 flex gap-3 flex-shrink-0">
                    <button type="button" onclick="document.getElementById('upload-memory-modal').classList.add('hidden')" class="btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn-primary flex-1">
                        <span class="material-symbols-outlined">cloud_upload</span> Upload & Share
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
