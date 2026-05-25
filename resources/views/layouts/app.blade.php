@php
    $trip = $trip ?? request()->route('trip');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'WanderGroup') }} - {{ $title ?? 'Dashboard' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Manage your group trips, itineraries, and expenses with WanderGroup.' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <style>
        @keyframes scale-up {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-scale-up {
            animation: scale-up 0.3s ease-out forwards;
        }
        #route-map { height: 180px; width: 100%; border-radius: 1.25rem; z-index: 1; }
        .leaflet-control-attribution { display: none; }
        
        [x-cloak] { display: none !important; }
        
        @media (max-width: 1023px) {
            .mobile-sidebar-container {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .mobile-sidebar-container.is-open {
                transform: translateX(0) !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-background text-on-background font-body antialiased min-h-screen" :class="sidebarOpen ? 'overflow-hidden' : ''" x-data="{ sidebarOpen: false }">

    {{-- ── Side Navigation (Desktop) ── --}}
    {{-- ── Mobile Overlay ── --}}
    <div x-show="sidebarOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         :class="{'block': sidebarOpen, 'hidden': !sidebarOpen}"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-backdrop-mobile lg:hidden cursor-pointer"
         style="display: none;" x-cloak></div>

    {{-- ── Side Navigation ── --}}
    <nav x-cloak
         :class="sidebarOpen ? 'sidebar-visible shadow-elevation-4 is-open' : 'sidebar-hidden'"
         class="fixed left-0 top-0 h-screen p-stack-md pb-32 border-r border-surface-variant/30 bg-surface-container-low w-72 flex flex-col z-sidebar-mobile transition-transform duration-300 ease-in-out lg:translate-x-0 mobile-sidebar-container overflow-y-auto hide-scrollbar">
        
        <div class="flex items-center justify-between mb-stack-lg">
            <a href="{{ route('dashboard') }}" class="font-headline text-headline-md text-primary font-bold">WanderGroup</a>
            <button type="button" @click.stop="sidebarOpen = false" class="lg:hidden p-2 rounded-full hover:bg-surface-variant/20 flex items-center justify-center">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        {{-- User Profile --}}
        <div class="mb-stack-md pb-stack-md border-b border-surface-variant/50">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full overflow-hidden bg-surface-variant flex items-center justify-center">
                    @if(Auth::user()->avatar_url)
                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="font-headline text-headline-md text-on-surface-variant">{{ Auth::user()->initials }}</span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-label text-label-md text-on-surface truncate">{{ Auth::user()->name }}</p>
                    <p class="font-label text-label-sm text-on-surface-variant">Explorer</p>
                </div>

                {{-- Notification Bell --}}
                @if(isset($trip))
                @php
                    $unreadCount = \App\Models\ActivityLog::where('trip_id', $trip->id)
                        ->where('user_id', '!=', Auth::id())
                        ->where('created_at', '>=', now()->subDay())
                        ->count();
                @endphp
                <a href="{{ route('trips.activities', $trip) }}" class="relative p-2 rounded-xl hover:bg-surface-container-low transition-colors group" title="Notifications">
                    <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors" @if($unreadCount > 0) style="font-variation-settings: 'FILL' 1;" @endif>notifications</span>
                    @if($unreadCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-error text-white text-[10px] font-bold flex items-center justify-center px-1 animate-pulse shadow-sm">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    @endif
                </a>
                @endif
            </div>
        </div>

        {{-- Navigation Links --}}
        <ul class="flex-1 flex flex-col gap-2">
            <li>
                <a href="{{ route('dashboard') }}"
                   class="nav-item {{ request()->routeIs('dashboard') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <span class="material-symbols-outlined" @if(request()->routeIs('dashboard')) style="font-variation-settings: 'FILL' 1;" @endif>dashboard</span>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('explore') }}"
                   class="nav-item {{ request()->routeIs('explore') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <span class="material-symbols-outlined" @if(request()->routeIs('explore')) style="font-variation-settings: 'FILL' 1;" @endif>explore</span>
                    Explore
                </a>
            </li>
            @if(isset($trip))
                <li>
                    <a href="{{ route('trips.itinerary', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.itinerary') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.itinerary')) style="font-variation-settings: 'FILL' 1;" @endif>event_note</span>
                        Itinerary
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.members', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.members') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.members')) style="font-variation-settings: 'FILL' 1;" @endif>group</span>
                        Members
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.activities', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.activities') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.activities')) style="font-variation-settings: 'FILL' 1;" @endif>history</span>
                        Activity
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.voting', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.voting') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined">how_to_vote</span>
                        Voting
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.finances', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.finances') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined">payments</span>
                        Finances
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.budget', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.budget') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.budget')) style="font-variation-settings: 'FILL' 1;" @endif>account_balance</span>
                        Budget
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.checklist', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.checklist') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined">checklist</span>
                        Checklist
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.settlements', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.settlements') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined">account_balance_wallet</span>
                        Settle Up
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.ai', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.ai') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.ai')) style="font-variation-settings: 'FILL' 1;" @endif>auto_awesome</span>
                        WanderAI
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.documents', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.documents') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.documents')) style="font-variation-settings: 'FILL' 1;" @endif>folder_open</span>
                        Documents
                    </a>
                </li>
                <li>
                    <a href="{{ route('trips.memories', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.memories') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.memories')) style="font-variation-settings: 'FILL' 1;" @endif>photo_library</span>
                        Memories
                    </a>
                </li>
                @if($trip->isOrganizer(Auth::user()))
                <li>
                    <a href="{{ route('trips.edit', $trip) }}"
                       class="nav-item {{ request()->routeIs('trips.edit') ? 'nav-item-active' : 'nav-item-inactive' }}">
                        <span class="material-symbols-outlined" @if(request()->routeIs('trips.edit')) style="font-variation-settings: 'FILL' 1;" @endif>settings_applications</span>
                        Trip Settings
                    </a>
                </li>
                @endif
            @endif
        </ul>

        {{-- Bottom Links --}}
        <div class="mt-auto pt-stack-md border-t border-surface-variant/50">
            <ul class="flex flex-col gap-2">
                <li>
                    <a href="{{ route('profile.edit') }}" class="nav-item nav-item-inactive">
                        <span class="material-symbols-outlined">settings</span>
                        Settings
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-item nav-item-inactive w-full text-left">
                            <span class="material-symbols-outlined">logout</span>
                            Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    {{-- ── Main Content Area ── --}}
    <main class="flex-1 lg:ml-72 pb-24 lg:pb-8 w-full max-w-[1280px] mx-auto px-container-padding-mobile md:px-container-padding-desktop pt-stack-lg">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 p-4 rounded-xl bg-success/10 text-success border border-success/20 flex items-center gap-3"
                 x-data="{ show: true }" x-show="show" x-transition
                 x-init="setTimeout(() => show = false, 5000)">
                <span class="material-symbols-outlined">check_circle</span>
                <span class="font-label text-label-md">{{ session('success') }}</span>
                <button @click="show = false" class="ml-auto"><span class="material-symbols-outlined text-[18px]">close</span></button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 rounded-xl bg-error/10 text-error border border-error/20 flex items-center gap-3"
                 x-data="{ show: true }" x-show="show" x-transition>
                <span class="material-symbols-outlined">error</span>
                <span class="font-label text-label-md">{{ session('error') }}</span>
                <button @click="show = false" class="ml-auto"><span class="material-symbols-outlined text-[18px]">close</span></button>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-5 rounded-2xl bg-error/10 text-error border border-error/20 flex flex-col gap-2"
                 x-data="{ show: true }" x-show="show" x-transition>
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-error font-bold">error</span>
                    <span class="font-headline text-label-md font-bold">Validation Error:</span>
                    <button @click="show = false" class="ml-auto flex items-center justify-center w-8 h-8 rounded-full hover:bg-error/10 transition-colors"><span class="material-symbols-outlined text-[18px]">close</span></button>
                </div>
                <ul class="list-disc list-inside font-body text-body-md pl-4 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- ── Bottom Navigation (Mobile) ── --}}
    <nav class="fixed bottom-0 left-0 w-full z-bottom-nav-mobile flex justify-around items-center px-4 py-3 pb-safe bg-surface/90 backdrop-blur-lg lg:hidden rounded-t-xl shadow-elevation-3">
        <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'bottom-nav-item-active' : '' }}">
            <span class="material-symbols-outlined" @if(request()->routeIs('dashboard')) style="font-variation-settings: 'FILL' 1;" @endif>home</span>
        </a>
        @if(isset($trip))
            <a href="{{ route('trips.itinerary', $trip) }}" class="bottom-nav-item {{ request()->routeIs('trips.itinerary') ? 'bottom-nav-item-active' : '' }}">
                <span class="material-symbols-outlined">map</span>
            </a>
            <a href="{{ route('trips.voting', $trip) }}" class="bottom-nav-item {{ request()->routeIs('trips.voting') ? 'bottom-nav-item-active' : '' }}">
                <span class="material-symbols-outlined">thumbs_up_down</span>
            </a>
            <a href="{{ route('trips.finances', $trip) }}" class="bottom-nav-item {{ request()->routeIs('trips.finances') ? 'bottom-nav-item-active' : '' }}">
                <span class="material-symbols-outlined">receipt_long</span>
            </a>
            <a href="{{ route('trips.documents', $trip) }}" class="bottom-nav-item {{ request()->routeIs('trips.documents') ? 'bottom-nav-item-active' : '' }}">
                <span class="material-symbols-outlined">folder_open</span>
            </a>
            <a href="{{ route('trips.activities', $trip) }}" class="bottom-nav-item {{ request()->routeIs('trips.activities') ? 'bottom-nav-item-active' : '' }}">
                <span class="material-symbols-outlined">history</span>
            </a>
        @endif
        <a href="#" class="bottom-nav-item" :class="{'bottom-nav-item-active': sidebarOpen}" @click.prevent.stop="sidebarOpen = !sidebarOpen">
            <span class="material-symbols-outlined">menu</span>
        </a>
    </nav>
    <div id="toast-container" class="fixed bottom-safe right-4 z-[110] flex flex-col gap-2 pointer-events-none"></div>
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');
            const color = type === 'success' ? 'primary' : (type === 'error' ? 'error' : 'secondary');

            toast.className = `p-4 rounded-xl shadow-elevation-3 bg-surface text-on-surface border border-${color}/20 flex items-center gap-3 animate-fade-in pointer-events-auto`;
            toast.innerHTML = `
                <span class="material-symbols-outlined text-${color}">${icon}</span>
                <span class="font-label text-label-md">${message}</span>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
    @stack('scripts')
</body>
</html>
