<x-app-layout>
    @php $title = $trip->title . ' - Members'; @endphp

    <header class="mb-stack-lg flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="font-display text-display-lg text-on-background mb-1">Member Management</h1>
            <p class="font-body text-body-lg text-on-surface-variant">Manage your travel crew and invitations for {{ $trip->destination }}.</p>
        </div>
        <button onclick="document.getElementById('invite-modal').classList.remove('hidden')" class="btn-primary shadow-lg shadow-primary/20">
            <span class="material-symbols-outlined">person_add</span> Invite Friends
        </button>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter">
        
        {{-- Member List Section --}}
        <div class="lg:col-span-7 space-y-stack-md">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-display text-headline-sm text-on-surface">Trip Members ({{ $trip->members->count() }})</h3>
            </div>

            <div class="space-y-4">
                @foreach($trip->members as $member)
                <div class="card p-5 flex items-center gap-4 border-0 bg-white shadow-elevation-1 hover:shadow-elevation-2 transition-all group">
                    {{-- Avatar/Initial --}}
                    <div class="w-14 h-14 rounded-2xl {{ $member->id === $trip->created_by ? 'bg-tertiary-container text-on-tertiary-container' : 'bg-surface-variant text-on-surface-variant' }} flex items-center justify-center font-display text-headline-sm shadow-sm">
                        {{ $member->initials }}
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 class="font-headline text-label-lg text-on-surface truncate">{{ $member->name }}</h4>
                            @if($member->id === Auth::id())
                                <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary font-label text-[10px] uppercase">You</span>
                            @endif
                        </div>
                        <p class="font-body text-body-sm text-outline flex items-center gap-1 mt-1">
                            @if($member->id === $trip->created_by)
                                <span class="material-symbols-outlined text-tertiary text-[14px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                <span class="font-bold text-tertiary">Trip Creator</span>
                            @else
                                <span class="material-symbols-outlined text-[14px]">shield_person</span>
                                <span class="capitalize">{{ $member->pivot->role }}</span>
                            @endif
                            <span class="mx-1 opacity-30">•</span>
                            Joined {{ $member->pivot->joined_at ? \Illuminate\Support\Carbon::parse($member->pivot->joined_at)->format('M Y') : $member->created_at->format('M Y') }}
                        </p>
                    </div>

                    {{-- Actions (Only for Organizers and not the creator) --}}
                    @if($trip->isOrganizer(Auth::user()) && $member->id !== $trip->created_by)
                    <div class="flex items-center gap-2">
                        {{-- Role Toggle --}}
                        <form method="POST" action="{{ route('trips.members.role', [$trip, $member]) }}">
                            @csrf
                            @if($member->pivot->role === 'member')
                                <input type="hidden" name="role" value="organizer">
                                <button type="submit" class="w-10 h-10 rounded-xl hover:bg-primary/10 text-outline hover:text-primary transition-all flex items-center justify-center" title="Promote to Organizer">
                                    <span class="material-symbols-outlined text-[20px]">arrow_upward</span>
                                </button>
                            @else
                                <input type="hidden" name="role" value="member">
                                <button type="submit" class="w-10 h-10 rounded-xl hover:bg-surface-container-highest text-outline transition-all flex items-center justify-center" title="Demote to Member">
                                    <span class="material-symbols-outlined text-[20px]">arrow_downward</span>
                                </button>
                            @endif
                        </form>

                        {{-- Remove Member --}}
                        @if($member->id !== Auth::id())
                        <form method="POST" action="{{ route('trips.members.remove', [$trip, $member]) }}" onsubmit="return confirm('Remove {{ $member->name }} from this trip?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-10 h-10 rounded-xl hover:bg-error/10 text-outline hover:text-error transition-all flex items-center justify-center" title="Remove Member">
                                <span class="material-symbols-outlined text-[20px]">person_remove</span>
                            </button>
                        </form>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Invitations & Links Section --}}
        <div class="lg:col-span-5 space-y-stack-md">
            
            {{-- Quick Invite Link --}}
            <section class="card bg-gradient-to-br from-primary/5 to-secondary/5 border-primary/10">
                <h3 class="font-headline text-label-md text-on-surface font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">link</span>
                    Quick Join Link
                </h3>
                <div class="space-y-4" x-data="{ 
                    link: '{{ route('trips.join', $trip->getInviteCode()) }}',
                    copied: false,
                    copy() {
                        navigator.clipboard.writeText(this.link);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }
                }">
                    <div class="flex gap-2">
                        <input readonly :value="link" class="input-field !py-3 !px-4 flex-1 bg-white border-surface-variant/30 text-outline text-label-sm font-mono truncate" type="text">
                        <button @click="copy()" type="button" class="btn-primary !px-4 shrink-0 shadow-sm">
                            <span class="material-symbols-outlined text-[20px]" x-text="copied ? 'check' : 'content_copy'"></span>
                        </button>
                    </div>
                    <p class="font-body text-[12px] text-on-surface-variant italic">Share this link with anyone you want to join this trip. They will be added as a 'Member' automatically.</p>
                </div>
            </section>

            {{-- Pending Invitations --}}
            <section class="card">
                <h3 class="font-headline text-label-md text-on-surface font-bold mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">mail</span>
                        Pending Invitations
                    </span>
                    <span class="text-outline text-[11px] uppercase tracking-wider">{{ $trip->invitations->where('status', 'pending')->count() }} Total</span>
                </h3>
                
                <div class="space-y-3">
                    @forelse($trip->invitations->where('status', 'pending') as $invite)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low border border-surface-variant/30">
                        <div class="flex flex-col">
                            <span class="font-label text-label-sm text-on-surface">{{ $invite->email }}</span>
                            <span class="font-body text-[10px] text-outline">Sent {{ $invite->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <form method="POST" action="{{ route('invitation.decline', $invite->token) }}">
                                @csrf
                                <button type="submit" class="text-outline hover:text-error p-1 transition-colors" title="Cancel Invitation">
                                    <span class="material-symbols-outlined text-[18px]">cancel</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6">
                        <span class="material-symbols-outlined text-outline/30 text-[32px] mb-2">drafts</span>
                        <p class="font-body text-[12px] text-on-surface-variant italic">No pending invitations.</p>
                    </div>
                    @endforelse
                </div>
            </section>

        </div>
    </div>

    {{-- Invite Modal --}}
    <div id="invite-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-white rounded-3xl shadow-elevation-3 w-full max-w-md p-8 animate-scale-up">
            <header class="flex justify-between items-center mb-6">
                <h3 class="font-display text-headline-md text-on-surface">Send Invitations</h3>
                <button onclick="this.closest('#invite-modal').classList.add('hidden')" class="w-10 h-10 rounded-full hover:bg-surface-container-low flex items-center justify-center text-outline">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>
            <form method="POST" action="{{ route('trips.invite', $trip) }}" class="space-y-5">
                @csrf
                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Friend's Emails</label>
                    <textarea class="input-field !py-3 !px-4 resize-none" name="emails" rows="4" placeholder="alex@example.com, sam@example.com" required></textarea>
                    <p class="font-body text-[11px] text-on-surface-variant">Separate multiple emails with commas.</p>
                </div>
                <button type="submit" class="btn-primary w-full py-3 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined">send</span> Send Invites
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
