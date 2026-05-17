<x-app-layout>
    @php $title = $trip->title . ' - Voting'; @endphp

    <header class="mb-stack-lg flex justify-between items-end">
        <div>
            <h1 class="font-display text-display-lg text-on-background mb-1">Group Voting</h1>
            <p class="font-body text-body-lg text-on-surface-variant">Vote on activities and make group decisions together.</p>
        </div>
        <button onclick="document.getElementById('create-poll-modal').classList.remove('hidden')" class="btn-primary">
            <span class="material-symbols-outlined">add</span> New Poll
        </button>
    </header>

    {{-- Active Polls --}}
    <section class="mb-stack-lg">
        <h2 class="font-headline text-headline-md text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-tertiary-container">how_to_vote</span> Active Polls
        </h2>
        @forelse($activePolls as $poll)
        <div class="card mb-4 border border-tertiary-container/20">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="font-headline text-headline-md text-on-surface">{{ $poll->question }}</h3>
                    @if($poll->description)
                    <p class="font-body text-body-md text-on-surface-variant mt-1">{{ $poll->description }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="chip bg-tertiary-container/20 text-tertiary">Active</span>
                    @if($trip->isOrganizer(Auth::user()) || $poll->created_by === Auth::id())
                    <form method="POST" action="{{ route('trips.polls.close', [$trip, $poll]) }}" onsubmit="return confirm('Close this poll?')">
                        @csrf
                        <button type="submit" class="text-outline hover:text-error transition-colors p-1 rounded-lg hover:bg-surface-container-low" title="Close poll">
                            <span class="material-symbols-outlined text-[18px]">lock</span>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($poll->options as $option)
                @php
                    $voted = $option->votes->contains('user_id', Auth::id());
                    $total = max($poll->totalVotes(), 1);
                    $pct = round(($option->votes->count() / $total) * 100);
                @endphp
                <form method="POST" action="{{ route('trips.polls.vote', [$trip, $option]) }}">
                    @csrf
                    <button type="submit" class="w-full text-left p-4 rounded-xl border {{ $voted ? 'border-primary bg-primary-fixed/10 ring-1 ring-primary/20' : 'border-surface-variant hover:border-primary/50' }} transition-all flex items-center gap-3 group active:scale-[0.98]">
                        <div class="w-10 h-10 rounded-lg bg-{{ $voted ? 'primary/10' : 'surface-container' }} flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-{{ $voted ? 'primary' : 'on-surface-variant' }}">{{ $option->icon ?? 'check_circle' }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="font-label text-label-md text-on-surface">{{ $option->title }}</div>
                            @if($option->description)
                            <div class="font-body text-[12px] text-on-surface-variant">{{ $option->description }}</div>
                            @endif
                            <div class="font-label text-label-sm text-on-surface-variant mt-1">
                                {{ $option->votes->count() }} vote{{ $option->votes->count() !== 1 ? 's' : '' }}
                                @if($voted) • <span class="text-primary font-bold">Your vote</span> @endif
                            </div>
                            <div class="mt-2 h-1.5 bg-surface-variant rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </button>
                </form>
                @endforeach
            </div>
            <div class="mt-4 flex justify-between items-center text-label-sm font-label text-outline">
                <span>Created by {{ $poll->creator->name }}</span>
                <span>{{ $poll->totalVotes() }} total votes</span>
            </div>
        </div>
        @empty
        <div class="card text-center py-8">
            <span class="material-symbols-outlined text-[48px] text-primary/30 mb-4">how_to_vote</span>
            <h4 class="font-headline text-headline-md text-on-surface mb-2">No active polls</h4>
            <p class="font-body text-body-md text-on-surface-variant mb-4">Create a poll to get the group's opinion!</p>
            <button onclick="document.getElementById('create-poll-modal').classList.remove('hidden')" class="btn-secondary">
                <span class="material-symbols-outlined">add</span> Create Poll
            </button>
        </div>
        @endforelse
    </section>

    {{-- Closed Polls --}}
    @if($closedPolls->isNotEmpty())
    <section>
        <h2 class="font-headline text-headline-md text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-outline">history</span> Closed Polls
        </h2>
        @foreach($closedPolls as $poll)
        <div class="card mb-4 opacity-75">
            <h3 class="font-headline text-headline-md text-on-surface mb-3">{{ $poll->question }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($poll->options->sortByDesc(fn($o) => $o->votes->count()) as $option)
                @php $total = max($poll->totalVotes(), 1); $pct = round(($option->votes->count() / $total) * 100); @endphp
                <div class="p-3 rounded-xl bg-surface-container-low flex items-center gap-3 {{ $loop->first ? 'border border-primary/20' : '' }}">
                    @if($loop->first) <span class="material-symbols-outlined text-primary text-[16px]">emoji_events</span> @endif
                    <div class="flex-1">
                        <div class="font-label text-label-md text-on-surface">{{ $option->title }}</div>
                        <div class="h-1.5 bg-surface-variant rounded-full overflow-hidden mt-1">
                            <div class="h-full bg-{{ $loop->first ? 'primary' : 'outline-variant' }} rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    <span class="font-label text-label-sm text-on-surface-variant">{{ $option->votes->count() }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </section>
    @endif

    {{-- Create Poll Modal --}}
    <div id="create-poll-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-surface-container-lowest rounded-2xl shadow-elevation-3 w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" x-data="{ options: [{title:'', icon:''}, {title:'', icon:''}] }">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-headline text-headline-md text-on-surface">Create New Poll</h3>
                <button onclick="this.closest('#create-poll-modal').classList.add('hidden')" class="text-outline hover:text-primary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST" action="{{ route('trips.polls.store', $trip) }}" class="space-y-4">
                @csrf
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Question *</label>
                    <input class="input-field pl-4" name="question" type="text" placeholder="e.g. What should we do on Day 3?" required>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Description</label>
                    <textarea class="input-field pl-4 resize-none" name="description" rows="2" placeholder="More context for the group"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Type</label>
                        <select class="input-field pl-4" name="type">
                            <option value="single">Single Choice</option>
                            <option value="multiple">Multiple Choice</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Deadline</label>
                        <input class="input-field pl-4" name="deadline" type="datetime-local">
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Options *</label>
                    <template x-for="(opt, idx) in options" :key="idx">
                        <div class="flex gap-2 mb-2">
                            <input :name="'options['+idx+'][title]'" x-model="opt.title" class="input-field pl-4 flex-1" placeholder="Option title" required>
                            <button type="button" @click="options.length > 2 && options.splice(idx, 1)" class="text-outline hover:text-error p-2" :class="options.length <= 2 ? 'opacity-30 pointer-events-none' : ''">
                                <span class="material-symbols-outlined text-[18px]">close</span>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="options.push({title:'', icon:''})" class="text-primary font-label text-label-sm flex items-center gap-1 hover:underline mt-1" x-show="options.length < 10">
                        <span class="material-symbols-outlined text-[16px]">add</span> Add Option
                    </button>
                </div>
                <button type="submit" class="btn-primary w-full mt-2">
                    <span class="material-symbols-outlined">how_to_vote</span> Create Poll
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
