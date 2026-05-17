<x-app-layout>
    @php $title = 'Account Settings'; @endphp

    <div class="max-w-4xl mx-auto space-y-6 pb-12">
        <header class="mb-6">
            <h1 class="font-display text-display-md text-on-background mb-1">Account Settings</h1>
            <p class="font-body text-body-md text-on-surface-variant">Manage your profile, preferences, and security settings.</p>
        </header>

        @if(session('success'))
            <div class="bg-primary/10 border border-primary/20 text-primary px-4 py-3 rounded-2xl font-label text-label-md flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('patch')

            {{-- 1. Profile Header Card (Avatar Section) --}}
            <div class="card bg-white border-0 shadow-elevation-1 p-6 flex flex-col sm:flex-row items-center gap-6">
                <div class="relative group">
                    <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-primary/20 group-hover:border-primary transition-all shadow-md relative bg-surface-container">
                        <img id="avatar-preview" 
                             src="{{ $user->avatar ? asset('storage/' . $user->avatar) : asset('images/avatar_sarah_1778982960514.png') }}" 
                             class="w-full h-full object-cover" 
                             alt="Avatar">
                    </div>
                    <label for="avatar-input" class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-primary text-on-primary flex items-center justify-center cursor-pointer shadow-md hover:bg-primary-dark transition-colors">
                        <span class="material-symbols-outlined text-[18px]">photo_camera</span>
                    </label>
                    <input type="file" name="avatar" id="avatar-input" class="hidden" accept="image/*" onchange="previewAvatar(this)">
                </div>
                
                <div class="flex-grow text-center sm:text-left">
                    <h2 class="font-display text-headline-md text-on-surface mb-1">{{ $user->name }}</h2>
                    <p class="font-body text-body-md text-on-surface-variant mb-3">{{ $user->bio ?? 'Global Explorer & Photographer' }}</p>
                    <button type="button" onclick="document.getElementById('avatar-input').click()" class="btn-ghost !py-1.5 !px-4 !text-[12px] border border-outline-variant/30 rounded-full hover:bg-primary/5 hover:text-primary transition-all font-bold">
                        Change Photo
                    </button>
                </div>
            </div>

            {{-- 2. Personal Information Card --}}
            <div class="card bg-white border-0 shadow-elevation-1 p-6 space-y-6">
                <h3 class="font-display text-headline-sm text-primary border-b border-surface-variant/30 pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined">person</span> Personal Information
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="font-label text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Full Name</label>
                        <input class="input-field pl-4" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                        @error('name') <span class="text-error text-label-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex flex-col gap-1.5">
                        <label class="font-label text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Email Address</label>
                        <input class="input-field pl-4" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                        @error('email') <span class="text-error text-label-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="font-label text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Phone Number</label>
                        <input class="input-field pl-4" name="phone_number" type="text" value="{{ old('phone_number', $user->travel_preferences['phone_number'] ?? '') }}" placeholder="+1 (555) 012-3456">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="font-label text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Preferred Language</label>
                        <select class="input-field pl-4" name="language">
                            @php $lang = $user->travel_preferences['language'] ?? 'English (US)'; @endphp
                            @foreach(['English (US)', 'Bahasa Indonesia', 'Español', 'Français', '日本語'] as $opt)
                            <option value="{{ $opt }}" {{ $lang === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="font-label text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Travel Bio</label>
                    <textarea class="input-field pl-4 resize-none" name="bio" rows="3" placeholder="Passionate about discovering hidden gems...">{{ old('bio', $user->bio) }}</textarea>
                </div>
            </div>

            {{-- 3. Travel Preferences Card --}}
            <div class="card bg-white border-0 shadow-elevation-1 p-6 space-y-6">
                <h3 class="font-display text-headline-sm text-primary border-b border-surface-variant/30 pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined">explore</span> Travel Preferences
                </h3>
                
                {{-- Dietary Requirements --}}
                <div class="space-y-3">
                    <label class="font-label text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Dietary Requirements</label>
                    <div id="dietary-container" class="flex flex-wrap gap-2 items-center">
                        @php 
                            $diets = $user->travel_preferences['dietary_requirements'] ?? ['Vegetarian', 'Gluten-Free']; 
                        @endphp
                        @foreach($diets as $diet)
                        <span class="diet-tag inline-flex items-center gap-1 bg-[#FFF0E6] text-[#FF5E00] px-3 py-1 rounded-full text-[12px] font-bold shadow-sm">
                            {{ $diet }}
                            <input type="hidden" name="dietary_requirements[]" value="{{ $diet }}">
                            <button type="button" onclick="removeDietTag(this)" class="hover:text-red-700 font-bold ml-1 text-[14px]">×</button>
                        </span>
                        @endforeach
                        <button type="button" onclick="showAddDietModal()" class="btn-ghost !py-1 !px-3 !text-[11px] border border-outline-variant/30 rounded-full hover:bg-primary/5 hover:text-primary transition-all font-bold">
                            + Add New
                        </button>
                    </div>
                </div>

                {{-- Preferred Travel Style --}}
                <div class="space-y-3">
                    <label class="font-label text-label-sm text-on-surface-variant uppercase tracking-wider font-bold">Preferred Travel Style</label>
                    
                    @php 
                        $style = $user->travel_preferences['travel_style'] ?? 'Adventure'; 
                    @endphp
                    <input type="hidden" name="travel_style" id="travel_style_input" value="{{ $style }}">

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach([
                            ['id' => 'Adventure', 'icon' => 'explore', 'label' => 'Adventure'],
                            ['id' => 'Relaxing', 'icon' => 'bed', 'label' => 'Relaxing'],
                            ['id' => 'Cultural', 'icon' => 'museum', 'label' => 'Cultural'],
                            ['id' => 'Luxury', 'icon' => 'diamond', 'label' => 'Luxury']
                        ] as $item)
                        <button type="button" 
                                onclick="selectTravelStyle('{{ $item['id'] }}')" 
                                id="style-card-{{ $item['id'] }}" 
                                class="travel-style-card flex flex-col items-center justify-center p-4 rounded-2xl border-2 transition-all gap-2 text-center bg-white shadow-sm hover:border-primary/50 hover:shadow-md
                                       {{ $style === $item['id'] ? 'border-primary bg-primary/5 text-primary shadow-sm' : 'border-outline-variant/20 text-on-surface-variant' }}">
                            <span class="material-symbols-outlined text-[24px] {{ $style === $item['id'] ? 'text-primary' : 'text-outline' }}">{{ $item['icon'] }}</span>
                            <span class="text-[12px] font-bold">{{ $item['label'] }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 4. Action Save Changes Button --}}
            <div class="flex justify-end gap-3 pt-2">
                <button type="submit" class="btn-primary !px-8 shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined">save</span> Save Changes
                </button>
            </div>
        </form>

        {{-- 5. Password Update Card --}}
        <div class="card bg-white border-0 shadow-elevation-1 p-6 space-y-6">
            <h3 class="font-display text-headline-sm text-on-surface border-b border-surface-variant/30 pb-3 flex items-center gap-2">
                <span class="material-symbols-outlined">lock</span> Update Password
            </h3>
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        {{-- 6. Danger Zone Card --}}
        <div class="card bg-white border border-error/20 p-6 space-y-6">
            <h3 class="font-display text-headline-sm text-error border-b border-error/10 pb-3 flex items-center gap-2">
                <span class="material-symbols-outlined">warning</span> Danger Zone
            </h3>
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>

    {{-- Interactive Add Diet Tag Dialog --}}
    <div id="diet-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-on-surface/30 backdrop-blur-sm" onclick="hideAddDietModal()"></div>
        <div class="relative bg-white rounded-3xl shadow-elevation-3 w-full max-w-sm p-6 animate-scale-up">
            <h4 class="font-display text-headline-sm text-on-surface mb-4">Add Dietary Requirement</h4>
            <input type="text" id="diet-input" class="input-field pl-4 mb-4" placeholder="e.g. Halal, Vegan, Nut-free" onkeyup="if(event.key === 'Enter') confirmAddDiet()">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="hideAddDietModal()" class="btn-ghost !py-2 !px-4 text-[12px]">Cancel</button>
                <button type="button" onclick="confirmAddDiet()" class="btn-primary !py-2 !px-4 text-[12px]">Add</button>
            </div>
        </div>
    </div>

    {{-- Javascript For Interactivity --}}
    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function selectTravelStyle(styleId) {
            // Update hidden input
            document.getElementById('travel_style_input').value = styleId;
            
            // Remove active classes from all cards
            document.querySelectorAll('.travel-style-card').forEach(card => {
                card.classList.remove('border-primary', 'bg-primary/5', 'text-primary');
                card.classList.add('border-outline-variant/20', 'text-on-surface-variant');
                const icon = card.querySelector('.material-symbols-outlined');
                icon.classList.remove('text-primary');
                icon.classList.add('text-outline');
            });
            
            // Add active classes to selected card
            const activeCard = document.getElementById('style-card-' + styleId);
            activeCard.classList.remove('border-outline-variant/20', 'text-on-surface-variant');
            activeCard.classList.add('border-primary', 'bg-primary/5', 'text-primary');
            const activeIcon = activeCard.querySelector('.material-symbols-outlined');
            activeIcon.classList.remove('text-outline');
            activeIcon.classList.add('text-primary');
        }

        function showAddDietModal() {
            document.getElementById('diet-modal').classList.remove('hidden');
            document.getElementById('diet-input').focus();
        }

        function hideAddDietModal() {
            document.getElementById('diet-modal').classList.add('hidden');
            document.getElementById('diet-input').value = '';
        }

        function confirmAddDiet() {
            const input = document.getElementById('diet-input');
            const value = input.value.trim();
            if (value) {
                // Check if already exists
                let exists = false;
                document.querySelectorAll('input[name="dietary_requirements[]"]').forEach(tagInput => {
                    if (tagInput.value.toLowerCase() === value.toLowerCase()) exists = true;
                });
                
                if (!exists) {
                    const container = document.getElementById('dietary-container');
                    const btn = container.lastElementChild; // the "+ Add New" button
                    
                    const span = document.createElement('span');
                    span.className = 'diet-tag inline-flex items-center gap-1 bg-[#FFF0E6] text-[#FF5E00] px-3 py-1 rounded-full text-[12px] font-bold shadow-sm';
                    span.innerHTML = `
                        ${value}
                        <input type="hidden" name="dietary_requirements[]" value="${value}">
                        <button type="button" onclick="removeDietTag(this)" class="hover:text-red-700 font-bold ml-1 text-[14px]">×</button>
                    `;
                    container.insertBefore(span, btn);
                }
            }
            hideAddDietModal();
        }

        function removeDietTag(btn) {
            btn.parentElement.remove();
        }
    </script>
</x-app-layout>
