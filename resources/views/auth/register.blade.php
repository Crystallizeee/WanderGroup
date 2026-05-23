<x-guest-layout>
    @php $title = 'Register'; @endphp

    <div class="flex-1 flex items-center justify-center p-4 relative overflow-hidden bg-slate-50">
        {{-- Ambient Glow Blobs --}}
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-primary/10 blur-[100px] pointer-events-none animate-pulse"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-secondary/10 blur-[100px] pointer-events-none animate-pulse" style="animation-delay: 2s;"></div>

        <div class="w-full max-w-[1000px] flex rounded-2xl overflow-hidden bg-surface-container-lowest shadow-elevation-3 min-h-[600px] z-10">
            {{-- Image Section (Desktop Only) --}}
            <div class="hidden lg:block lg:w-1/2 relative overflow-hidden">
                <img src="{{ asset('images/auth_register.png') }}" alt="Start Your Journey" class="absolute inset-0 w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-br from-secondary/30 to-primary/30 mix-blend-multiply"></div>
                <div class="absolute inset-0 flex flex-col justify-end p-10 z-10" style="background: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.3) 60%, transparent 100%) !important;">
                    <h2 class="font-headline text-headline-lg mb-2" style="color: #ffffff !important;">Start Your Journey</h2>
                    <p class="font-body text-body-lg" style="color: rgba(255, 255, 255, 0.9) !important;">Join thousands of groups planning unforgettable trips together.</p>
                </div>
            </div>

            {{-- Register Form Section --}}
            <div class="w-full lg:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                <div class="mb-8 text-center lg:text-left">
                    <h1 class="font-headline text-headline-lg text-primary font-bold tracking-tight mb-2">Create Account</h1>
                    <p class="font-body text-body-md text-on-surface-variant">Let's get you set up for your next adventure.</p>
                </div>

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

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <div class="space-y-1">
                        <label class="block font-label text-label-md text-on-surface" for="reg-name">Full Name</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant">person</span>
                            <input class="input-field" id="reg-name" name="name" type="text" placeholder="Your full name" value="{{ old('name') }}" required autofocus>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-label text-label-md text-on-surface" for="reg-email">Email</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant">mail</span>
                            <input class="input-field" id="reg-email" name="email" type="email" placeholder="you@example.com" value="{{ old('email') }}" required>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-label text-label-md text-on-surface" for="reg-password">Password</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant">lock</span>
                            <input class="input-field" id="reg-password" name="password" type="password" placeholder="Create a password" required>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block font-label text-label-md text-on-surface" for="reg-password-confirm">Confirm Password</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant">lock</span>
                            <input class="input-field" id="reg-password-confirm" name="password_confirmation" type="password" placeholder="Confirm your password" required>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-primary text-on-primary rounded-full font-label text-label-md hover:bg-surface-tint transition-colors shadow-elevation-1 active:scale-95 duration-200">
                        Create Account
                    </button>
                </form>

                {{-- Google Sign-In Separator & Button --}}
                @if(config('services.google.client_id'))
                    <div class="relative flex items-center justify-center my-6">
                        <div class="border-t border-outline-variant/30 w-full"></div>
                        <span class="absolute px-3 bg-surface-container-lowest text-label-md text-on-surface-variant font-body">or continue with</span>
                        <div class="border-t border-outline-variant/30 w-full"></div>
                    </div>

                    <div class="flex flex-col items-center justify-center w-full">
                        <div id="g_id_onload"
                             data-client_id="{{ config('services.google.client_id') }}"
                             data-context="signup"
                             data-ux_mode="redirect"
                             data-login_uri="{{ route('auth.google') }}"
                             data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin w-full flex justify-center"
                             data-type="standard"
                             data-shape="pill"
                             data-theme="outline"
                             data-text="signup_with"
                             data-size="large"
                             data-logo_alignment="left"
                             data-width="384">
                        </div>
                    </div>
                @endif

                <p class="mt-6 text-center font-body text-body-md text-on-surface-variant">
                    Already have an account?
                    <a class="font-label text-label-md text-primary hover:text-surface-tint transition-colors" href="{{ route('login') }}">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
