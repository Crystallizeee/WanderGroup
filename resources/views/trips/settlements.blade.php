<x-app-layout>
    @php $title = $trip->title . ' - Settle Up'; @endphp

    <header class="mb-stack-lg flex justify-between items-end">
        <div>
            <h1 class="font-display text-display-lg text-on-background mb-1">Settle Up</h1>
            <p class="font-body text-body-lg text-on-surface-variant">Smart debt optimization for {{ $trip->title }}.</p>
        </div>
        <a href="{{ route('trips.finances', $trip) }}" class="btn-ghost">
            <span class="material-symbols-outlined">arrow_back</span> Back to Finances
        </a>
    </header>

    {{-- Summary Card --}}
    <div class="card md:col-span-2 relative overflow-hidden mb-stack-lg">
        <div class="absolute top-0 right-0 w-80 h-80 bg-primary-fixed-dim/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center gap-6 relative z-10">
            <div class="flex-1">
                <h3 class="font-label text-label-md text-on-surface-variant mb-1">Remaining Unsettled</h3>
                <div class="font-display text-display-lg text-on-background">{{ rupiah($totalDebt) }}</div>
                <p class="font-body text-body-md text-on-surface-variant mt-1">
                    Only <span class="font-bold text-primary">{{ $settlements->count() }}</span> transaction{{ $settlements->count() !== 1 ? 's' : '' }} needed to settle all debts
                </p>
            </div>
            <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-[48px]" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
            </div>
        </div>
    </div>

    {{-- Suggested Settlements --}}
    @if($settlements->count() > 0)
    <section class="mb-stack-lg">
        <h2 class="font-headline text-headline-md text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">swap_horiz</span>
            Optimized Settlements
        </h2>
        <div class="flex flex-col gap-4">
            @foreach($settlements as $idx => $s)
            <div class="card border border-surface-container-high/50 flex flex-col sm:flex-row items-center gap-4 p-5">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    {{-- From --}}
                    <div class="w-12 h-12 rounded-full bg-tertiary/10 text-tertiary flex items-center justify-center font-headline font-bold text-[14px] shrink-0">
                        {{ $s['from_user']->initials }}
                    </div>
                    <div class="hidden sm:block min-w-0">
                        <p class="font-label text-label-md text-on-surface truncate">{{ $s['from_user']->name }}</p>
                        <p class="font-label text-label-sm text-outline">pays</p>
                    </div>

                    {{-- Arrow + Amount --}}
                    <div class="flex flex-col items-center mx-4">
                        <div class="font-headline text-headline-md text-primary">{{ rupiah($s['amount']) }}</div>
                        <div class="flex items-center text-outline mt-1">
                            <div class="w-8 h-[2px] bg-primary/40"></div>
                            <span class="material-symbols-outlined text-primary text-[20px]">arrow_forward</span>
                            <div class="w-8 h-[2px] bg-primary/40"></div>
                        </div>
                    </div>

                    {{-- To --}}
                    <div class="w-12 h-12 rounded-full bg-primary/10 text-primary flex items-center justify-center font-headline font-bold text-[14px] shrink-0">
                        {{ $s['to_user']->initials }}
                    </div>
                    <div class="hidden sm:block min-w-0">
                        <p class="font-label text-label-md text-on-surface truncate">{{ $s['to_user']->name }}</p>
                        <p class="font-label text-label-sm text-outline">receives</p>
                    </div>
                </div>

                {{-- Settle Button --}}
                <form method="POST" action="{{ route('trips.settlements.settle', $trip) }}" class="shrink-0">
                    @csrf
                    <input type="hidden" name="from_user" value="{{ $s['from_user']->id }}">
                    <input type="hidden" name="to_user" value="{{ $s['to_user']->id }}">
                    <input type="hidden" name="amount" value="{{ $s['amount'] }}">
                    <button type="submit" class="btn-primary py-2 px-5 text-label-sm" onclick="return confirm('Mark this as paid?')">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span> Mark Paid
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </section>
    @else
    <div class="card text-center py-12 mb-stack-lg">
        <span class="material-symbols-outlined text-[64px] text-primary/20 mb-4" style="font-variation-settings: 'FILL' 1;">celebration</span>
        <h3 class="font-headline text-headline-md text-on-surface mb-2">All Settled!</h3>
        <p class="font-body text-body-md text-on-surface-variant">Everyone is even. No payments needed.</p>
    </div>
    @endif

    {{-- Net Balances Overview --}}
    <section class="mb-stack-lg">
        <h2 class="font-headline text-headline-md text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-outline">analytics</span> Balance Summary
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($netBalances as $userId => $net)
            @php $user = $membersById[$userId] ?? null; @endphp
            @if($user)
            <div class="card p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full {{ $net > 0 ? 'bg-primary/10 text-primary' : ($net < 0 ? 'bg-tertiary/10 text-tertiary' : 'bg-surface-variant text-outline') }} flex items-center justify-center font-bold text-[12px]">
                    {{ $user->initials }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-label text-label-md text-on-surface truncate">{{ $user->name }}</p>
                    <p class="font-label text-label-sm {{ $net > 0 ? 'text-primary' : ($net < 0 ? 'text-tertiary' : 'text-outline') }}">
                        @if(abs($net) < 0.01) Lunas
                        @elseif($net > 0) Menerima {{ rupiah($net) }}
                        @else Berhutang {{ rupiah(abs($net)) }}
                        @endif
                    </p>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </section>

    {{-- Completed Settlements --}}
    @if($completedSettlements->count() > 0)
    <section>
        <h2 class="font-headline text-headline-md text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-outline">history</span> Settlement History
        </h2>
        <div class="card p-0 overflow-hidden">
            @foreach($completedSettlements as $s)
            <div class="flex items-center gap-4 p-4 {{ !$loop->last ? 'border-b border-surface-variant/50' : '' }}">
                <div class="w-10 h-10 rounded-lg bg-primary/5 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">check_circle</span>
                </div>
                <div class="flex-1">
                    <p class="font-label text-label-md text-on-surface">
                        {{ $s->payer->name }} <span class="text-outline">→</span> {{ $s->payee->name }}
                    </p>
                    <p class="font-label text-label-sm text-outline">
                        via {{ ucfirst($s->payment_method ?? 'cash') }} • {{ $s->created_at->format('M d, Y') }}
                    </p>
                </div>
                <div class="font-label text-label-md text-primary">{{ rupiah($s->amount) }}</div>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</x-app-layout>
