<x-app-layout>
    @php $title = $trip->title . ' - Budget Management'; @endphp

    <header class="mb-stack-lg flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <div class="font-label text-label-sm text-primary uppercase tracking-widest flex items-center gap-1.5 mb-1.5 font-bold">
                <span class="material-symbols-outlined text-[14px]">location_on</span> {{ $trip->destination }}
            </div>
            <h1 class="font-display text-display-lg text-on-background mb-1">Budget Management</h1>
            <p class="font-body text-body-lg text-on-surface-variant">Track your planned costs versus actual spent by category.</p>
        </div>
        <button onclick="document.getElementById('edit-categories-modal').classList.remove('hidden')" class="btn-primary shadow-lg shadow-primary/20 !py-1.5 !px-3.5 !text-[12px] flex items-center gap-1 h-fit self-start sm:self-end">
            <span class="material-symbols-outlined text-[16px]">edit_note</span> Manage Budgets
        </button>
    </header>

    {{-- 1. Premium Top Stats Card (Triple Grid + Progress) --}}
    <div class="card bg-white border-0 shadow-elevation-1 p-6 mb-stack-lg flex flex-col gap-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 divide-y md:divide-y-0 md:divide-x divide-surface-variant/30">
            <div class="flex flex-col gap-1 pb-4 md:pb-0">
                <span class="font-label text-label-sm text-outline uppercase tracking-wider">Total Budget</span>
                <span class="font-display text-[32px] text-on-background font-bold">{{ rupiah($totalBudget) }}</span>
            </div>
            <div class="flex flex-col gap-1 py-4 md:py-0 md:pl-6">
                <span class="font-label text-label-sm text-outline uppercase tracking-wider">Allocated</span>
                <span class="font-display text-[32px] text-primary font-bold">{{ rupiah($totalAllocated) }}</span>
            </div>
            <div class="flex flex-col gap-1 pt-4 md:pt-0 md:pl-6">
                <span class="font-label text-label-sm text-outline uppercase tracking-wider">Remaining</span>
                <span class="font-display text-[32px] text-tertiary font-bold">{{ rupiah($remainingBudget) }}</span>
            </div>
        </div>

        <div class="space-y-2 mt-2">
            <div class="flex justify-between items-center font-label text-[11px] uppercase tracking-wider text-outline font-bold">
                <span>Budget Allocation</span>
                <span class="text-primary">{{ $utilizationPercentage }}% Used</span>
            </div>
            <div class="h-3 bg-surface-container rounded-full overflow-hidden w-full">
                <div class="h-full bg-primary transition-all duration-1000 shadow-sm" style="width: {{ $utilizationPercentage }}%"></div>
            </div>
        </div>
    </div>

    {{-- 2. Categories Grid --}}
    <section class="mb-stack-lg">
        <h2 class="font-display text-headline-md text-on-background mb-4">Categories</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            @php
                $categories = [
                    'lodging' => ['label' => 'Lodging', 'icon' => 'bed', 'bg' => 'bg-blue-50 text-blue-600 border-blue-100', 'color' => 'text-blue-600'],
                    'food' => ['label' => 'Food & Drinks', 'icon' => 'restaurant', 'bg' => 'bg-orange-50 text-orange-600 border-orange-100', 'color' => 'text-orange-600'],
                    'transport' => ['label' => 'Transport', 'icon' => 'flight', 'bg' => 'bg-indigo-50 text-indigo-600 border-indigo-100', 'color' => 'text-indigo-600'],
                    'activities' => ['label' => 'Activities', 'icon' => 'sailing', 'bg' => 'bg-teal-50 text-teal-600 border-teal-100', 'color' => 'text-teal-600'],
                    'misc' => ['label' => 'Misc', 'icon' => 'more_horiz', 'bg' => 'bg-gray-100 text-gray-600 border-gray-200', 'color' => 'text-gray-600']
                ];
            @endphp

            @foreach($categories as $key => $cat)
            <div class="card p-5 bg-white border border-outline-variant/10 shadow-sm flex flex-col gap-4 hover:shadow-md transition-all">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center border {{ $cat['bg'] }}">
                        <span class="material-symbols-outlined text-[20px]">{{ $cat['icon'] }}</span>
                    </div>
                    <span class="font-headline text-label-md text-on-surface font-bold">{{ $cat['label'] }}</span>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between text-[11px] font-label text-outline">
                        <span>Budgeted</span>
                        <span class="font-bold text-on-surface-variant">{{ rupiah($categoryBudgets[$key] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between text-[11px] font-label text-outline">
                        <span>Actual Spent</span>
                        <span class="font-bold {{ $cat['color'] }}">{{ rupiah($actualSpent[$key] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- 3. Detailed Planned Items Table --}}
    <section class="mb-stack-lg">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-display text-headline-md text-on-background">Detailed Items</h2>
            <a href="{{ route('trips.itinerary', $trip) }}" class="btn-ghost !py-1.5 !px-3 text-label-sm border border-outline-variant/30 rounded-full flex items-center gap-1 font-bold">
                <span class="material-symbols-outlined text-[16px]">add_circle</span> Add Item
            </a>
        </div>
        <div class="card p-0 overflow-hidden bg-white border-0 shadow-elevation-1">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-surface-container-low/50 font-label text-[11px] text-outline uppercase tracking-wider">
                        <tr>
                            <th class="p-5">Item Name</th>
                            <th class="p-5">Category</th>
                            <th class="p-5">Estimated Cost</th>
                            <th class="p-5">Status</th>
                            <th class="p-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="font-body text-body-md">
                        @forelse($detailedItems as $item)
                        @php
                            $catMap = [
                                'hotel' => ['label' => 'Lodging', 'class' => 'bg-blue-50 text-blue-700 border-blue-100', 'icon' => 'bed'],
                                'restaurant' => ['label' => 'Food & Drinks', 'class' => 'bg-orange-50 text-orange-700 border-orange-100', 'icon' => 'restaurant'],
                                'flight' => ['label' => 'Transport', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-100', 'icon' => 'flight'],
                                'transport' => ['label' => 'Transport', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-100', 'icon' => 'flight'],
                                'activity' => ['label' => 'Activities', 'class' => 'bg-teal-50 text-teal-700 border-teal-100', 'icon' => 'sailing'],
                                'other' => ['label' => 'Misc', 'class' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'more_horiz']
                            ];
                            $meta = $catMap[$item->type] ?? $catMap['other'];
                        @endphp
                        <tr class="border-b border-surface-variant/20 hover:bg-surface-bright transition-colors group">
                            <td class="p-5">
                                <p class="font-headline text-label-md text-on-surface font-bold leading-none">{{ $item->title }}</p>
                                <span class="font-body text-[10px] text-outline leading-none mt-1 inline-block">{{ $item->location }}</span>
                            </td>
                            <td class="p-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-[11px] font-bold {{ $meta['class'] }}">
                                    <span class="material-symbols-outlined text-[14px]">{{ $meta['icon'] }}</span>
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="p-5 font-display text-headline-sm text-on-surface font-bold">
                                {{ rupiah($item->estimated_cost ?? 0) }}
                            </td>
                            <td class="p-5">
                                @if($item->status === 'confirmed')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-success/10 text-success text-[11px] font-bold">
                                    <span class="material-symbols-outlined text-[14px] font-bold">check_circle</span> Confirmed
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-warning/10 text-warning text-[11px] font-bold">
                                    <span class="material-symbols-outlined text-[14px] font-bold">pending</span> Tentative
                                </span>
                                @endif
                            </td>
                            <td class="p-5 text-right">
                                <a href="{{ route('trips.itinerary', $trip) }}" class="inline-flex w-8 h-8 rounded-full items-center justify-center text-outline hover:bg-primary/10 hover:text-primary transition-all" title="Manage Itinerary">
                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-10 text-center text-outline font-body text-body-md">
                                Belum ada agenda rencana pengeluaran terperinci di itinerary.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Edit Category Budgets Modal --}}
    <div id="edit-categories-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-on-surface/30 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-3xl shadow-elevation-3 w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto animate-scale-up">
            <header class="flex justify-between items-center mb-6">
                <h3 class="font-display text-headline-md text-on-surface font-bold">Manage Category Budgets</h3>
                <button onclick="this.closest('#edit-categories-modal').classList.add('hidden')" class="w-10 h-10 rounded-full hover:bg-surface-container-low flex items-center justify-center text-outline">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>

            <form method="POST" action="{{ route('trips.budget.categories', $trip) }}" class="space-y-4">
                @csrf
                <div class="space-y-3">
                    <div class="flex flex-col gap-1">
                        <label class="font-label text-label-sm text-outline font-bold">Lodging Budget (Rp)</label>
                        <input class="input-field pl-4" name="lodging" type="number" value="{{ (int) ($categoryBudgets['lodging'] ?? 0) }}" required min="0">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-label text-label-sm text-outline font-bold">Food & Drinks Budget (Rp)</label>
                        <input class="input-field pl-4" name="food" type="number" value="{{ (int) ($categoryBudgets['food'] ?? 0) }}" required min="0">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-label text-label-sm text-outline font-bold">Transport Budget (Rp)</label>
                        <input class="input-field pl-4" name="transport" type="number" value="{{ (int) ($categoryBudgets['transport'] ?? 0) }}" required min="0">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-label text-label-sm text-outline font-bold">Activities Budget (Rp)</label>
                        <input class="input-field pl-4" name="activities" type="number" value="{{ (int) ($categoryBudgets['activities'] ?? 0) }}" required min="0">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="font-label text-label-sm text-outline font-bold">Misc Budget (Rp)</label>
                        <input class="input-field pl-4" name="misc" type="number" value="{{ (int) ($categoryBudgets['misc'] ?? 0) }}" required min="0">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-surface-variant/30">
                    <button type="button" onclick="this.closest('#edit-categories-modal').classList.add('hidden')" class="btn-ghost text-[12px] !py-2 !px-4">Cancel</button>
                    <button type="submit" class="btn-primary text-[12px] !py-2 !px-4 shadow-md">Save Budgets</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
