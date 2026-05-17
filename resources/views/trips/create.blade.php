<x-guest-layout>
    @php $title = 'Create Trip'; @endphp

    {{-- Top Navigation --}}
    <header class="glass shadow-elevation-1 fixed top-0 right-0 left-0 z-40 flex justify-between items-center px-container-padding-mobile md:px-container-padding-desktop h-16">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary font-bold" style="font-variation-settings: 'FILL' 1;">explore</span>
            <span class="font-headline text-headline-lg font-bold text-primary">WanderGroup</span>
        </div>
        <a href="{{ route('dashboard') }}" class="text-on-surface-variant hover:text-primary transition-colors flex items-center gap-2 font-label text-label-md">
            <span class="material-symbols-outlined">close</span>
            <span class="hidden md:inline">Cancel</span>
        </a>
    </header>

    <main class="flex-1 mt-16 flex flex-col lg:flex-row">
        {{-- Illustration Side (Desktop) --}}
        <div class="hidden lg:flex w-1/3 bg-primary-container/10 p-container-padding-desktop flex-col justify-between items-start border-r border-surface-variant relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-32 -mt-32 w-96 h-96 bg-secondary-container rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
            <div class="absolute bottom-0 left-0 -ml-32 -mb-32 w-96 h-96 bg-tertiary-container rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
            <div class="relative z-10 w-full">
                <div class="mb-stack-lg">
                    <span class="font-label text-label-sm uppercase tracking-wider text-primary font-bold bg-primary-fixed px-3 py-1 rounded-full">Step 1 of 3</span>
                </div>
                <h1 class="font-display text-display-lg text-on-surface mb-stack-md">Let's craft your next adventure.</h1>
                <p class="font-body text-body-lg text-on-surface-variant">Start by laying down the foundation. Where to, and when? We'll handle the complex planning so you can focus on the fun parts.</p>
            </div>
            <div class="relative z-10 w-full h-64 mt-stack-lg rounded-xl overflow-hidden shadow-elevation-2 bg-gradient-to-br from-primary/20 to-secondary/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-[80px] text-primary/30" style="font-variation-settings: 'FILL' 1;">flight_takeoff</span>
            </div>
        </div>

        {{-- Form Side --}}
        <div class="flex-1 px-container-padding-mobile py-stack-lg md:px-container-padding-desktop md:py-container-padding-desktop flex flex-col items-center justify-center relative bg-surface">
            <div class="w-full max-w-2xl relative z-10">

                {{-- Stepper Progress --}}
                <div class="mb-stack-lg flex items-center justify-between relative">
                    <div class="absolute top-1/2 left-0 w-full h-[2px] bg-surface-variant -z-10 -translate-y-1/2"></div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center font-label text-label-md shadow-elevation-1 ring-4 ring-surface">1</div>
                        <span class="font-label text-label-sm text-primary font-bold hidden md:block">Basic Info</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-surface-container-highest text-on-surface-variant flex items-center justify-center font-label text-label-md ring-4 ring-surface">2</div>
                        <span class="font-label text-label-sm text-on-surface-variant hidden md:block">Budget & Style</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full bg-surface-container-highest text-on-surface-variant flex items-center justify-center font-label text-label-md ring-4 ring-surface">3</div>
                        <span class="font-label text-label-sm text-on-surface-variant hidden md:block">Invite Friends</span>
                    </div>
                </div>

                {{-- Step Content Card --}}
                <div class="card">
                    <h2 class="font-headline text-headline-md text-on-surface mb-stack-sm">The Basics</h2>
                    <p class="font-body text-body-md text-on-surface-variant mb-stack-lg">Where are you heading and when? You can always adjust this later.</p>

                    @if ($errors->any())
                        <div class="mb-4 p-4 rounded-xl bg-error/10 text-on-error-container border border-error/20">
                            <ul class="text-label-sm font-label space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[14px]">error</span>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('trips.store') }}" enctype="multipart/form-data" class="space-y-stack-md">
                        @csrf

                        {{-- Trip Title --}}
                        <div class="flex flex-col gap-2">
                            <label class="font-label text-label-md text-on-surface" for="trip-title">Trip Name</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">edit</span>
                                <input class="input-field" id="trip-title" name="title" type="text" placeholder="e.g. Summer in Bali" value="{{ old('title') }}" required>
                            </div>
                        </div>

                        {{-- Destination --}}
                        <div class="flex flex-col gap-2">
                            <label class="font-label text-label-md text-on-surface" for="trip-destination">Destination</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">location_on</span>
                                <input class="input-field" id="trip-destination" name="destination" type="text" placeholder="e.g. Tokyo, Japan" value="{{ old('destination') }}" required>
                            </div>
                        </div>

                        {{-- Date Range --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-stack-md">
                            <div class="flex flex-col gap-2">
                                <label class="font-label text-label-md text-on-surface" for="trip-start-date">Start Date</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">calendar_month</span>
                                    <input class="input-field" id="trip-start-date" name="start_date" type="date" value="{{ old('start_date') }}" required>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="font-label text-label-md text-on-surface" for="trip-end-date">End Date</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">calendar_month</span>
                                    <input class="input-field" id="trip-end-date" name="end_date" type="date" value="{{ old('end_date') }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Budget --}}
                        <div class="flex flex-col gap-2">
                            <label class="font-label text-label-md text-on-surface" for="trip-budget">Budget (optional)</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">payments</span>
                                <input class="input-field" id="trip-budget" name="budget" type="number" step="0.01" placeholder="e.g. 5000" value="{{ old('budget') }}">
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="flex flex-col gap-2">
                            <label class="font-label text-label-md text-on-surface" for="trip-description">Description (optional)</label>
                            <textarea class="w-full px-4 py-3 rounded-xl border border-outline-variant bg-surface-container-lowest text-on-surface font-body text-body-md focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all shadow-sm resize-none" id="trip-description" name="description" rows="3" placeholder="What's this trip about?">{{ old('description') }}</textarea>
                        </div>

                        {{-- Quick Select Chips --}}
                        <div class="pt-stack-sm">
                            <span class="font-label text-label-sm text-on-surface-variant block mb-2">Popular suggestions:</span>
                            <div class="flex flex-wrap gap-2" x-data>
                                @foreach(['Bali, Indonesia', 'Tokyo, Japan', 'Rome, Italy', 'Banff, Canada', 'Paris, France'] as $suggestion)
                                <button type="button" class="chip bg-secondary-container/30 text-on-secondary-container hover:bg-secondary-container transition-colors"
                                        @click="document.getElementById('trip-destination').value = '{{ $suggestion }}'">
                                    {{ $suggestion }}
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="mt-stack-lg flex justify-between items-center">
                        <a href="{{ route('dashboard') }}" class="font-label text-label-md text-on-surface-variant hover:text-primary px-4 py-2 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="btn-primary py-3 px-8">
                            Create Trip
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</x-guest-layout>
