<x-guest-layout>
    @php $title = 'Travel Together, Plan Better'; @endphp

    {{-- ── TopNavBar ── --}}
    <header class="glass shadow-sm fixed top-0 w-full z-50" x-data="{ mobileMenuOpen: false }">
        <div class="flex justify-between items-center px-container-padding-mobile md:px-container-padding-desktop py-4 max-w-7xl mx-auto">
            <div class="flex items-center gap-gutter">
                <div class="font-headline text-headline-md font-bold text-primary">WanderGroup</div>
                <nav class="hidden md:flex gap-6 items-center">
                    <a href="#features" class="text-on-surface-variant hover:text-primary transition-colors font-label text-label-md hover:bg-surface-container-low px-3 py-2 rounded-lg">Features</a>
                    <a href="#how-it-works" class="text-on-surface-variant hover:text-primary transition-colors font-label text-label-md hover:bg-surface-container-low px-3 py-2 rounded-lg">How It Works</a>
                    <a href="#testimonials" class="text-on-surface-variant hover:text-primary transition-colors font-label text-label-md hover:bg-surface-container-low px-3 py-2 rounded-lg">Testimonials</a>
                </nav>
            </div>
            <div class="flex items-center gap-2 md:gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-primary font-label text-label-md hover:underline hidden md:block">Sign In</a>
                    <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                @endauth

                {{-- Hamburger Menu --}}
                <button class="md:hidden p-2 rounded-xl hover:bg-surface-container-low text-on-surface" @click="mobileMenuOpen = !mobileMenuOpen">
                    <span class="material-symbols-outlined" x-text="mobileMenuOpen ? 'close' : 'menu'">menu</span>
                </button>
            </div>
        </div>

        {{-- Mobile Menu Drawer --}}
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-4"
             @click.away="mobileMenuOpen = false"
             class="absolute top-full left-0 w-full bg-surface shadow-elevation-3 border-t border-surface-variant/30 md:hidden p-6 flex flex-col gap-4 z-50"
             style="display: none;" x-cloak>
            <a href="#features" @click="mobileMenuOpen = false" class="font-label text-label-lg text-on-surface p-3 rounded-xl hover:bg-surface-variant/20 flex items-center gap-3">
                <span class="material-symbols-outlined">featured_play_list</span> Features
            </a>
            <a href="#how-it-works" @click="mobileMenuOpen = false" class="font-label text-label-lg text-on-surface p-3 rounded-xl hover:bg-surface-variant/20 flex items-center gap-3">
                <span class="material-symbols-outlined">help</span> How It Works
            </a>
            <a href="#testimonials" @click="mobileMenuOpen = false" class="font-label text-label-lg text-on-surface p-3 rounded-xl hover:bg-surface-variant/20 flex items-center gap-3">
                <span class="material-symbols-outlined">forum</span> Testimonials
            </a>
            <hr class="border-surface-variant/30">
            @guest
                <a href="{{ route('login') }}" class="font-label text-label-lg text-primary p-3 rounded-xl hover:bg-primary/5 flex items-center gap-3">
                    <span class="material-symbols-outlined">login</span> Sign In
                </a>
            @endguest
        </div>
        
        {{-- Mobile Menu Overlay --}}
        <div x-show="mobileMenuOpen" @click="mobileMenuOpen = false" 
             class="fixed inset-0 bg-black/20 backdrop-blur-sm z-40 md:hidden"
             style="display: none;" x-cloak></div>
    </header>

    {{-- ── Main Content ── --}}
    <main class="flex-grow pt-[88px]">

        {{-- 1. Hero Section --}}
        <section class="relative w-full px-container-padding-mobile md:px-container-padding-desktop py-stack-lg md:py-[120px] flex items-center justify-center overflow-hidden min-h-[80vh]">
            <div class="absolute inset-0 z-0">
                <img src="{{ asset('images/hero_travel_group.png') }}" alt="Group of friends traveling" class="absolute inset-0 w-full h-full object-cover opacity-20" />
                <div class="absolute inset-0 bg-gradient-to-b from-surface/90 via-surface/95 to-surface-container-lowest z-10 backdrop-blur-[3px]"></div>
            </div>
            <div class="relative z-20 text-center max-w-4xl mx-auto flex flex-col items-center gap-stack-lg">
                <div class="flex flex-col gap-stack-sm">
                    <h1 class="font-display text-display-lg md:text-[64px] md:leading-[72px] text-on-surface tracking-tight">
                        Travel Together, <br class="hidden md:block"><span class="text-primary">Plan Better</span>
                    </h1>
                    <p class="font-body text-body-lg text-on-surface-variant max-w-2xl mx-auto mt-4">
                        Coordinate itineraries, split expenses instantly, and chat with your group—all in one beautiful place. Stop juggling spreadsheets and start exploring.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 mt-8">
                    <a href="{{ route('register') }}" class="btn-primary px-8 py-4">
                        Get Started for Free
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">arrow_forward</span>
                    </a>
                    <a href="#how-it-works" class="btn-secondary px-8 py-4">
                        See How it Works
                    </a>
                </div>

                {{-- Floating UI Elements --}}
                <div class="mt-16 relative w-full max-w-5xl mx-auto hidden md:block h-[400px]">
                    {{-- Split Bill Card --}}
                    <div class="absolute top-10 left-0 bg-surface-container-lowest p-6 rounded-xl shadow-elevation-3 w-80 transform -rotate-3 z-10">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold font-headline">JD</div>
                            <div>
                                <div class="font-label text-label-md text-on-surface">Dinner at Luigi's</div>
                                <div class="font-label text-label-sm text-on-surface-variant">Split equally</div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center border-t border-surface-container-highest pt-4">
                            <span class="font-body text-body-md text-on-surface-variant">You owe</span>
                            <span class="font-headline text-headline-md text-primary">$45.00</span>
                        </div>
                    </div>

                    {{-- Itinerary Card --}}
                    <div class="absolute top-0 right-10 bg-surface-container-lowest p-6 rounded-xl shadow-elevation-3 w-96 transform rotate-2 z-20">
                        <h3 class="font-headline text-headline-md text-on-surface mb-4">Day 2: Bali</h3>
                        <div class="flex flex-col gap-4 relative">
                            <div class="absolute left-3 top-2 bottom-2 w-0.5 bg-primary/20"></div>
                            <div class="flex gap-4 relative z-10">
                                <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-1">
                                    <span class="material-symbols-outlined text-[14px] text-on-primary">done</span>
                                </div>
                                <div>
                                    <div class="font-label text-label-md text-on-surface">Morning Surf Session</div>
                                    <div class="font-label text-label-sm text-on-surface-variant">9:00 AM - Kuta Beach</div>
                                </div>
                            </div>
                            <div class="flex gap-4 relative z-10">
                                <div class="w-6 h-6 rounded-full bg-surface-container-highest flex items-center justify-center flex-shrink-0 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-primary"></span>
                                </div>
                                <div>
                                    <div class="font-label text-label-md text-on-surface">Lunch at Cafe Organic</div>
                                    <div class="font-label text-label-sm text-on-surface-variant">1:00 PM - Seminyak</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 2. How It Works --}}
        <section id="how-it-works" class="w-full px-container-padding-mobile md:px-container-padding-desktop py-[80px] bg-surface-container-lowest">
            <div class="max-w-7xl mx-auto flex flex-col items-center gap-stack-lg">
                <div class="text-center max-w-2xl">
                    <h2 class="font-headline text-headline-lg-mobile md:text-headline-lg text-on-surface mb-4">Simple, stress-free planning</h2>
                    <p class="font-body text-body-md text-on-surface-variant">Three steps to get your group aligned and ready for takeoff.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter w-full">
                    @foreach([
                        ['icon' => 'add_location', 'color' => 'primary-container', 'textColor' => 'on-primary-container', 'title' => '1. Create Trip', 'desc' => 'Set the dates, pick a destination, and establish a high-level budget for the group.'],
                        ['icon' => 'group_add', 'color' => 'secondary-container', 'textColor' => 'on-secondary-container', 'title' => '2. Invite Friends', 'desc' => 'Send a quick link. Everyone can add ideas, vote on activities, and sync calendars.'],
                        ['icon' => 'payments', 'color' => 'tertiary-container', 'textColor' => 'on-tertiary-container', 'title' => '3. Travel & Split', 'desc' => 'Log expenses on the go. We do the math so you can focus on the memories.'],
                    ] as $step)
                    <div class="bg-surface p-8 rounded-xl shadow-elevation-1 hover:shadow-elevation-2 transition-shadow flex flex-col items-center text-center gap-stack-sm group">
                        <div class="w-16 h-16 bg-{{ $step['color'] }} rounded-full flex items-center justify-center mb-4 text-{{ $step['textColor'] }} group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[32px]">{{ $step['icon'] }}</span>
                        </div>
                        <h3 class="font-headline text-headline-md text-on-surface">{{ $step['title'] }}</h3>
                        <p class="font-body text-body-md text-on-surface-variant">{{ $step['desc'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 3. Core Features (Bento Grid) --}}
        <section id="features" class="w-full px-container-padding-mobile md:px-container-padding-desktop py-[80px] bg-surface">
            <div class="max-w-7xl mx-auto flex flex-col gap-stack-lg">
                <div class="text-left max-w-2xl mb-8">
                    <h2 class="font-headline text-headline-lg-mobile md:text-headline-lg text-on-surface mb-4">Everything you need, nothing you don't.</h2>
                    <p class="font-body text-body-md text-on-surface-variant">Powerful tools hidden behind a simple, beautiful interface designed for travelers.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter auto-rows-[300px]">
                    {{-- Feature 1: Collaborative Planning --}}
                    <div class="md:col-span-8 bg-surface-container-lowest rounded-xl shadow-elevation-1 p-8 flex flex-col justify-between relative overflow-hidden group">
                        <div class="relative z-10 max-w-md">
                            <div class="chip bg-primary/10 text-primary mb-4">
                                <span class="material-symbols-outlined text-[16px]">edit_calendar</span> Itinerary Builder
                            </div>
                            <h3 class="font-headline text-headline-md text-on-surface mb-2">Collaborative Planning</h3>
                            <p class="font-body text-body-md text-on-surface-variant">Drag and drop activities, sync with maps, and let everyone vote on the daily schedule in real-time.</p>
                        </div>
                        <div class="absolute right-0 bottom-0 w-2/3 h-2/3 translate-x-10 translate-y-10 rounded-tl-xl bg-surface-container p-4 shadow-inner">
                            <div class="w-full h-full bg-white rounded-lg shadow-sm p-4 flex flex-col gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                <div class="h-8 bg-surface-variant rounded w-3/4"></div>
                                <div class="h-16 bg-surface-container-low rounded w-full mt-2"></div>
                                <div class="h-16 bg-surface-container-low rounded w-5/6"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Feature 2: Smart Split --}}
                    <div class="md:col-span-4 bg-surface-container-lowest rounded-xl shadow-elevation-1 p-8 flex flex-col justify-between relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="chip bg-tertiary-container/20 text-tertiary mb-4">
                                <span class="material-symbols-outlined text-[16px]">document_scanner</span> OCR Scanner
                            </div>
                            <h3 class="font-headline text-headline-md text-on-surface mb-2">Smart Split</h3>
                            <p class="font-body text-body-md text-on-surface-variant relative z-20">Just snap a photo of the receipt. We extract items and split them instantly.</p>
                        </div>
                        <img src="{{ asset('images/feature_ocr.png') }}" alt="OCR Scanner" class="absolute -right-8 -bottom-8 w-48 h-48 object-cover rounded-xl opacity-80 group-hover:opacity-100 transition-opacity transform group-hover:scale-105 duration-300 z-0" />
                    </div>

                    {{-- Feature 3: WanderAI --}}
                    <div class="md:col-span-4 bg-surface-container-lowest rounded-xl shadow-elevation-1 p-8 flex flex-col justify-between relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="chip bg-secondary-container/30 text-on-secondary-container mb-4">
                                <span class="material-symbols-outlined text-[16px]">robot_2</span> AI Assistant
                            </div>
                            <h3 class="font-headline text-headline-md text-on-surface mb-2">WanderAI</h3>
                            <p class="font-body text-body-md text-on-surface-variant">Ask for local recommendations or weather updates right in the group chat.</p>
                        </div>
                        <div class="absolute -left-4 -bottom-4 w-32 h-32 bg-secondary-container/20 rounded-full blur-2xl group-hover:bg-secondary-container/30 transition-colors"></div>
                    </div>

                    {{-- Feature 4: Visual Mapping --}}
                    <div class="md:col-span-8 bg-surface-container-lowest rounded-xl shadow-elevation-1 p-0 flex flex-col md:flex-row relative overflow-hidden group">
                        <div class="p-8 md:w-1/2 flex flex-col justify-center relative z-10 bg-surface-container-lowest/80 backdrop-blur-sm">
                            <div class="chip bg-primary-container/20 text-on-primary-container mb-4 w-max">
                                <span class="material-symbols-outlined text-[16px]">map</span> Interactive Maps
                            </div>
                            <h3 class="font-headline text-headline-md text-on-surface mb-2">Visual Mapping</h3>
                            <p class="font-body text-body-md text-on-surface-variant">See everyone's pinned locations and optimize your daily route automatically.</p>
                        </div>
                        <div class="md:w-1/2 h-full bg-surface-variant relative min-h-[200px] overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-surface-container-lowest to-transparent z-10 md:w-8"></div>
                            <img src="{{ asset('images/feature_map.png') }}" alt="Visual Mapping" class="absolute inset-0 w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 4. Testimonials --}}
        <section id="testimonials" class="w-full px-container-padding-mobile md:px-container-padding-desktop py-[80px] bg-surface-container-lowest border-t border-surface-container-high">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="font-headline text-headline-lg-mobile md:text-headline-lg text-on-surface mb-4">Trusted by Explorers</h2>
                    <p class="font-body text-body-md text-on-surface-variant">See how WanderGroup takes the friction out of group travel.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
                    <div class="bg-surface p-6 rounded-xl shadow-elevation-1">
                        <div class="flex items-center gap-4 mb-4">
                            <img src="{{ asset('images/avatar_sarah.png') }}" alt="Sarah Jenkins" class="w-12 h-12 rounded-full object-cover" />
                            <div>
                                <div class="font-label text-label-md text-on-surface">Sarah Jenkins</div>
                                <div class="font-label text-label-sm text-on-surface-variant">Bachelorette Trip Organizer</div>
                            </div>
                        </div>
                        <p class="font-body text-body-md text-on-surface italic">"The OCR receipt scanner saved our friendships. We had 12 girls in Nashville and nobody had to do math at the end of the trip. Brilliant."</p>
                    </div>
                    <div class="bg-surface p-6 rounded-xl shadow-elevation-1">
                        <div class="flex items-center gap-4 mb-4">
                            <img src="{{ asset('images/avatar_david.png') }}" alt="David Chen" class="w-12 h-12 rounded-full object-cover" />
                            <div>
                                <div class="font-label text-label-md text-on-surface">David Chen</div>
                                <div class="font-label text-label-sm text-on-surface-variant">Annual Ski Trip</div>
                            </div>
                        </div>
                        <p class="font-body text-body-md text-on-surface italic">"I love the collaborative itinerary. Being able to drag and drop activities and let the guys vote on what to do next made planning actually fun."</p>
                    </div>
                    <div class="bg-primary-container text-on-primary-container p-6 rounded-xl shadow-elevation-1 flex flex-col justify-center items-center text-center">
                        <div class="font-display text-display-lg font-bold mb-2">50k+</div>
                        <div class="font-headline text-headline-md mb-2">Trips Planned</div>
                        <p class="font-body text-body-md opacity-90">Join thousands of groups exploring the world seamlessly.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 5. Final CTA --}}
        <section class="w-full px-container-padding-mobile md:px-container-padding-desktop py-[120px] bg-primary relative overflow-hidden">
            <div class="absolute inset-0 z-0 opacity-10">
                <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-[100px] translate-x-1/2 -translate-y-1/2"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-[100px] -translate-x-1/2 translate-y-1/2"></div>
            </div>
            <div class="max-w-4xl mx-auto text-center relative z-10 flex flex-col items-center gap-stack-lg">
                <h2 class="font-display text-display-lg md:text-[56px] text-on-primary leading-tight">Start Planning Your Next Adventure</h2>
                <p class="font-body text-body-lg text-on-primary/90 max-w-2xl mx-auto">
                    Create your first trip in seconds. Invite your friends, and let WanderGroup handle the logistics.
                </p>
                <a href="{{ route('register') }}" class="bg-surface-container-lowest text-primary font-label text-label-md px-10 py-4 rounded-full hover:bg-surface transition-colors shadow-lg active:scale-95 duration-150 mt-4 text-[16px]">
                    Create Free Account
                </a>
            </div>
        </section>
    </main>

    {{-- ── Footer ── --}}
    <footer class="bg-surface-container-highest text-on-surface font-body text-body-md w-full py-stack-lg px-container-padding-mobile md:px-container-padding-desktop">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-gutter">
            <div class="flex flex-col gap-4">
                <div class="font-headline text-headline-md text-primary font-bold">WanderGroup</div>
                <p class="text-on-surface-variant">© {{ date('Y') }} WanderGroup. Travel together, plan better.</p>
            </div>
            <div class="flex flex-wrap gap-6 md:justify-end items-center">
                <a href="#features" class="text-on-surface-variant hover:text-primary underline decoration-primary underline-offset-4 opacity-80 hover:opacity-100 transition-opacity">Features</a>
                <a href="#" class="text-on-surface-variant hover:text-primary underline decoration-primary underline-offset-4 opacity-80 hover:opacity-100 transition-opacity">Pricing</a>
                <a href="#" class="text-on-surface-variant hover:text-primary underline decoration-primary underline-offset-4 opacity-80 hover:opacity-100 transition-opacity">About Us</a>
                <a href="#" class="text-on-surface-variant hover:text-primary underline decoration-primary underline-offset-4 opacity-80 hover:opacity-100 transition-opacity">Privacy Policy</a>
                <a href="#" class="text-on-surface-variant hover:text-primary underline decoration-primary underline-offset-4 opacity-80 hover:opacity-100 transition-opacity">Contact</a>
            </div>
        </div>
    </footer>
</x-guest-layout>
