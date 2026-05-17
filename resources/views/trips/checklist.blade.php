<x-app-layout>
    @php $title = $trip->title . ' - Checklist'; @endphp

    <header class="mb-stack-lg flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <p class="font-label text-[14px] text-secondary uppercase tracking-widest mb-1">{{ $trip->title }}</p>
            <h1 class="font-display text-display-lg text-on-background mb-1">Checklist & Packing</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('trips.checklist', ['trip' => $trip, 'suggest' => 1]) }}" class="flex items-center gap-2 px-4 py-2 border border-secondary text-secondary rounded-full font-label text-[14px] hover:bg-surface-container-low transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[20px]">auto_awesome</span> Get AI Suggestions
            </a>
            <button onclick="document.getElementById('add-item-modal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-full font-label text-[14px] hover:bg-primary/90 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[20px]">add</span> Add Item
            </button>
        </div>
    </header>

    {{-- AI Suggestions Section --}}
    @if(!empty($aiSuggestions))
    <section class="mb-stack-lg animate-fade-in">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="font-headline text-headline-md text-tertiary flex items-center gap-2">
                <span class="material-symbols-outlined">lightbulb</span> AI Recommendations
            </h2>
            <span class="px-2 py-0.5 rounded-full bg-tertiary/10 text-tertiary font-label text-[10px] uppercase tracking-wider">Based on Weather & Location</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($aiSuggestions as $suggestion)
            <div class="card p-4 border-l-4 border-tertiary bg-surface-container-low flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-2">
                        <span class="font-label text-label-md text-on-surface font-bold">{{ $suggestion['title'] }}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-outline-variant/20 text-outline uppercase font-bold">{{ $suggestion['category'] }}</span>
                    </div>
                    <p class="font-label text-[12px] text-on-surface-variant leading-relaxed">{{ $suggestion['reason'] }}</p>
                </div>
                <form method="POST" action="{{ route('trips.checklist.store', $trip) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="title" value="{{ $suggestion['title'] }}">
                    <input type="hidden" name="category" value="{{ $suggestion['category'] }}">
                    <button type="submit" class="w-full py-1.5 rounded-lg border border-tertiary/30 text-tertiary font-label text-label-sm hover:bg-tertiary hover:text-white transition-all flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">add</span> Add to List
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Smart Duplicate Warning --}}
    @php
        $duplicateItemsList = $trip->checklistItems()->where('is_shared', true)->whereIn('id', $duplicateIds)->get();
        $duplicateGroups = $duplicateItemsList->groupBy(fn($i) => strtolower(trim($i->title)));
    @endphp

    @if($duplicateGroups->count() > 0)
    <div class="bg-tertiary-container/10 border border-tertiary-container/30 rounded-xl p-4 flex items-start gap-3 mb-stack-lg shadow-sm">
        <span class="material-symbols-outlined text-tertiary-container mt-0.5">warning</span>
        <div>
            <h3 class="font-label text-[14px] text-on-surface font-bold">Smart Duplicate Detection</h3>
            @foreach($duplicateGroups as $dupTitle => $itemsGroup)
                <p class="font-body text-[14px] text-on-surface-variant mt-1">
                    {{ $itemsGroup->count() }} members are bringing "{{ $itemsGroup->first()->title }}". Consider coordinating to save luggage space.
                </p>
            @endforeach
        </div>
        <button class="ml-auto text-on-surface-variant hover:text-on-surface shrink-0" onclick="this.parentElement.remove()">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- SECTION 1: SHARED ITEMS (grouped by category)  --}}
    {{-- ═══════════════════════════════════════════════ --}}
    @if($groupedSharedItems->count() > 0)
    <section class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-secondary text-[22px]">group</span>
            <h2 class="font-headline text-[22px] text-on-surface font-bold">Shared Items</h2>
            <span class="font-label text-[12px] text-on-surface-variant ml-1">Items for the whole group</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($groupedSharedItems as $category => $categoryItems)
            <div class="bg-white rounded-xl p-6 shadow-sm {{ $loop->count <= 2 ? 'col-span-1 md:col-span-1 lg:col-span-1' : 'col-span-1' }} hover:shadow-md transition-shadow border border-surface-variant/30 {{ strtolower($category) === 'shared gear' ? 'border-l-4 border-l-primary' : '' }}">
                <div class="flex justify-between items-center mb-4 pb-2 border-b border-surface-variant/50">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-[22px]">
                            {{ match(strtolower($category)) {
                                'essentials' => 'luggage',
                                'shared gear' => 'camping',
                                'clothing' => 'checkroom',
                                'electronics' => 'devices',
                                'equipment' => 'sports_esports',
                                'documents' => 'description',
                                'toiletries' => 'cleaning_services',
                                default => 'checklist'
                            } }}
                        </span>
                        <h3 class="font-headline text-[18px] text-on-surface capitalize font-bold">{{ $category }}</h3>
                    </div>
                    @php
                        $checkedCount = $categoryItems->where('is_checked', true)->count();
                        $totalCount = $categoryItems->count();
                    @endphp
                    <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label text-[12px] font-bold">{{ $checkedCount }}/{{ $totalCount }} Packed</span>
                </div>

                <ul class="space-y-1">
                    @foreach($categoryItems as $item)
                    <li class="flex items-center justify-between p-3 hover:bg-surface-container-low rounded-lg transition-colors group cursor-pointer">
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('trips.checklist.toggle', [$trip, $item]) }}" class="flex-shrink-0">
                                @csrf
                                <button type="submit" class="w-6 h-6 rounded border-2 {{ $item->is_checked ? 'bg-primary border-primary text-white' : 'border-outline-variant hover:border-primary' }} flex items-center justify-center transition-all">
                                    @if($item->is_checked)
                                    <span class="material-symbols-outlined text-[16px] font-bold">check</span>
                                    @endif
                                </button>
                            </form>
                            <span class="font-body text-[15px] {{ $item->is_checked ? 'text-on-surface-variant line-through' : 'text-on-surface' }}">{{ $item->title }}</span>
                            @if($item->quantity > 1)
                            <span class="px-1.5 py-0.5 rounded bg-surface-variant text-on-surface-variant text-[10px] font-bold">×{{ $item->quantity }}</span>
                            @endif
                            @if(in_array($item->id, $duplicateIds))
                            <span class="w-2 h-2 rounded-full bg-tertiary-container animate-pulse" title="Duplicate detected"></span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if($item->assignee)
                            <div class="w-6 h-6 rounded-full bg-surface-variant border border-white flex items-center justify-center text-[10px] font-bold text-on-surface-variant" title="{{ $item->assignee->name }}">
                                {{ $item->assignee->initials }}
                            </div>
                            @else
                            <div class="flex -space-x-1.5">
                                @foreach($trip->members->take(3) as $m)
                                <div class="w-5 h-5 rounded-full bg-white border border-surface-variant flex items-center justify-center text-[7px] font-bold text-primary">{{ $m->initials }}</div>
                                @endforeach
                                @if($trip->members->count() > 3)
                                <div class="w-5 h-5 rounded-full bg-surface-variant border border-white flex items-center justify-center text-[7px] font-bold text-on-surface-variant">+{{ $trip->members->count() - 3 }}</div>
                                @endif
                            </div>
                            @endif

                            <form method="POST" action="{{ route('trips.checklist.destroy', [$trip, $item]) }}" onsubmit="return confirm('Remove?')" class="opacity-0 group-hover:opacity-100 transition-opacity">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[16px]">close</span></button>
                            </form>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══════════════════════════════════════════════ --}}
    {{-- SECTION 2: PERSONAL PACKING (grouped by member) --}}
    {{-- ═══════════════════════════════════════════════ --}}
    @if($personalByMember->count() > 0)
    <section class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-primary text-[22px]">person</span>
            <h2 class="font-headline text-[22px] text-on-surface font-bold">Personal Packing</h2>
            <span class="font-label text-[12px] text-on-surface-variant ml-1">Everyone packs their own</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($personalByMember as $memberId => $memberItems)
            @php
                $member = $trip->members->firstWhere('id', $memberId);
                if (!$member) continue;
                $memberChecked = $memberItems->where('is_checked', true)->count();
                $memberTotal = $memberItems->count();
                $memberPercent = $memberTotal > 0 ? round(($memberChecked / $memberTotal) * 100) : 0;
                $isMe = $memberId == auth()->id();
            @endphp
            <div class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow border border-surface-variant/30 {{ $isMe ? 'ring-2 ring-primary/30' : '' }}">
                {{-- Member Header with Progress --}}
                <div class="p-5 pb-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full {{ $isMe ? 'bg-primary text-on-primary' : 'bg-primary/10 text-primary' }} flex items-center justify-center text-[14px] font-bold shrink-0">
                        {{ $member->initials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="font-label text-[16px] font-bold text-on-surface truncate">
                                {{ $member->name }}
                                @if($isMe)
                                <span class="font-body text-[11px] text-primary font-normal ml-1">(You)</span>
                                @endif
                            </h3>
                            <span class="font-label text-[12px] {{ $memberPercent === 100 ? 'text-green-600' : 'text-on-surface-variant' }} font-bold shrink-0 ml-2">
                                {{ $memberPercent === 100 ? '✓ Ready!' : $memberChecked . '/' . $memberTotal }}
                            </span>
                        </div>
                        <div class="mt-1.5 h-1.5 bg-surface-variant rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 {{ $memberPercent === 100 ? 'bg-green-500' : 'bg-primary' }}" style="width: {{ $memberPercent }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Items List --}}
                <ul class="px-5 pb-4 space-y-1">
                    @foreach($memberItems->sortBy('is_checked') as $item)
                    <li class="flex items-center justify-between p-2 hover:bg-surface-container-low rounded-lg transition-colors group {{ !$isMe ? 'opacity-75' : '' }}">
                        <div class="flex items-center gap-3">
                            @if($isMe)
                            {{-- Own item: clickable checkbox --}}
                            <form method="POST" action="{{ route('trips.checklist.toggle', [$trip, $item]) }}" class="flex-shrink-0">
                                @csrf
                                <button type="submit" class="w-5 h-5 rounded border-2 {{ $item->is_checked ? 'bg-primary border-primary text-white' : 'border-outline-variant hover:border-primary' }} flex items-center justify-center transition-all">
                                    @if($item->is_checked)
                                    <span class="material-symbols-outlined text-[14px]">check</span>
                                    @endif
                                </button>
                            </form>
                            @else
                            {{-- Other person's item: read-only checkbox --}}
                            <div class="w-5 h-5 rounded border-2 {{ $item->is_checked ? 'bg-outline-variant border-outline-variant text-white' : 'border-outline-variant/50' }} flex items-center justify-center cursor-not-allowed" title="Only {{ $member->name }} can check this">
                                @if($item->is_checked)
                                <span class="material-symbols-outlined text-[14px]">check</span>
                                @endif
                            </div>
                            @endif
                            <span class="font-body text-[14px] {{ $item->is_checked ? 'text-on-surface-variant line-through' : 'text-on-surface' }}">{{ $item->title }}</span>
                            @if($item->quantity > 1)
                            <span class="px-1 py-0.5 rounded bg-surface-variant text-on-surface-variant text-[9px] font-bold">×{{ $item->quantity }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            @if(!$isMe)
                            <span class="material-symbols-outlined text-[14px] text-outline-variant opacity-0 group-hover:opacity-100 transition-opacity" title="Only {{ $member->name }} can modify this">lock</span>
                            @endif
                            @if($isMe)
                            <form method="POST" action="{{ route('trips.checklist.destroy', [$trip, $item]) }}" onsubmit="return confirm('Remove?')" class="opacity-0 group-hover:opacity-100 transition-opacity">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-outline hover:text-error transition-colors"><span class="material-symbols-outlined text-[14px]">close</span></button>
                            </form>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Empty State --}}
    @if($groupedSharedItems->isEmpty() && $personalByMember->isEmpty())
    <div class="bg-surface-bright rounded-xl border-2 border-dashed border-outline-variant p-12 flex flex-col items-center justify-center text-center">
        <span class="material-symbols-outlined text-[48px] text-primary/30 mb-4">luggage</span>
        <h4 class="font-headline text-[24px] text-on-surface mb-2 font-bold">No items yet</h4>
        <p class="font-body text-[16px] text-on-surface-variant mb-6">Start adding items to coordinate packing!</p>
        <div class="flex gap-3">
            <a href="{{ route('trips.checklist', ['trip' => $trip, 'suggest' => 1]) }}" class="flex items-center gap-2 px-4 py-2 border border-secondary text-secondary rounded-full font-label text-[14px] hover:bg-surface-container-low transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[20px]">auto_awesome</span> Get AI Suggestions
            </a>
            <button onclick="document.getElementById('add-item-modal').classList.remove('hidden')" class="flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-full font-label text-[14px] hover:bg-primary/90 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[20px]">add</span> Add First Item
            </button>
        </div>
    </div>
    @endif

    {{-- Add Item Modal --}}
    <div id="add-item-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-surface-container-lowest rounded-2xl shadow-elevation-3 w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-headline text-headline-md text-on-surface">Add Checklist Item</h3>
                <button onclick="this.closest('#add-item-modal').classList.add('hidden')" class="text-outline hover:text-primary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST" action="{{ route('trips.checklist.store', $trip) }}" class="space-y-4">
                @csrf
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Item Name *</label>
                    <input class="input-field pl-4" name="title" type="text" placeholder="e.g. Sunscreen SPF 50+" required>
                </div>

                {{-- Item Type (most important choice) --}}
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Item Type *</label>
                    <div class="grid grid-cols-2 gap-3" id="item-type-selector">
                        <label class="item-type-option flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-primary bg-primary/5 cursor-pointer transition-all hover:shadow-sm text-center">
                            <input type="radio" name="is_shared" value="1" class="hidden" checked>
                            <span class="material-symbols-outlined text-primary text-[28px]">group</span>
                            <div>
                                <div class="font-label text-[13px] text-on-surface font-bold">Shared</div>
                                <div class="font-body text-[11px] text-on-surface-variant">Group item</div>
                            </div>
                        </label>
                        <label class="item-type-option flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-outline-variant bg-white cursor-pointer transition-all hover:shadow-sm text-center">
                            <input type="radio" name="is_shared" value="0" class="hidden">
                            <span class="material-symbols-outlined text-outline text-[28px]">person</span>
                            <div>
                                <div class="font-label text-[13px] text-on-surface font-bold">Personal</div>
                                <div class="font-body text-[11px] text-on-surface-variant">Per person</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Category *</label>
                        <select class="input-field pl-4" name="category">
                            <option value="essentials">Essentials</option>
                            <option value="shared gear">Shared Gear</option>
                            <option value="clothing">Clothing</option>
                            <option value="electronics">Electronics</option>
                            <option value="equipment">Equipment</option>
                            <option value="toiletries">Toiletries</option>
                            <option value="documents">Documents</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Quantity</label>
                        <input class="input-field pl-4" name="quantity" type="number" min="1" max="99" value="1">
                    </div>
                </div>

                <div class="flex flex-col gap-2" id="assign-to-section">
                    <label class="font-label text-label-md text-on-surface">Assign to</label>
                    <select class="input-field pl-4" name="assigned_to">
                        <option value="">Everyone</option>
                        @foreach($trip->members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <p class="font-body text-[11px] text-on-surface-variant" id="assign-hint">Leave as "Everyone" for shared group items.</p>
                </div>

                <button type="submit" class="btn-primary w-full mt-2">
                    <span class="material-symbols-outlined">add</span> Add to Checklist
                </button>
            </form>
        </div>
    </div>

    <script>
        // Item Type Selector UI
        document.querySelectorAll('.item-type-option').forEach(label => {
            label.addEventListener('click', () => {
                document.querySelectorAll('.item-type-option').forEach(l => {
                    l.classList.remove('border-primary', 'bg-primary/5');
                    l.classList.add('border-outline-variant', 'bg-white');
                    l.querySelector('.material-symbols-outlined').classList.remove('text-primary');
                    l.querySelector('.material-symbols-outlined').classList.add('text-outline');
                });
                label.classList.add('border-primary', 'bg-primary/5');
                label.classList.remove('border-outline-variant', 'bg-white');
                label.querySelector('.material-symbols-outlined').classList.add('text-primary');
                label.querySelector('.material-symbols-outlined').classList.remove('text-outline');

                const isPersonal = label.querySelector('input').value === '0';
                const hint = document.getElementById('assign-hint');
                if (isPersonal) {
                    hint.textContent = 'Leave as "Everyone" to create one for each member automatically.';
                } else {
                    hint.textContent = 'Leave as "Everyone" for shared group items.';
                }
            });
        });
    </script>

    @push('scripts')
    <script type="module">
        if (window.Echo) {
            window.Echo.private('trip.{{ $trip->id }}')
                .listen('TripUpdated', (e) => {
                    if (e.type === 'checklist_toggled') {
                        if (e.message.user !== '{{ auth()->user()->name }}') {
                            showToast(`${e.message.user} just updated the checklist.`, 'info');
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    }
                });
        }
    </script>
    @endpush
</x-app-layout>
