<x-app-layout>
    @php $title = $trip->title . ' - WanderAI'; @endphp

    <div class="flex flex-col xl:flex-row gap-stack-lg h-[calc(100vh-120px)]">
        {{-- Chat Area --}}
        <div class="flex-1 flex flex-col bg-surface-container-lowest rounded-2xl shadow-elevation-1 overflow-hidden border border-surface-variant/30">

            {{-- Chat Header --}}
            <div class="flex items-center gap-4 p-5 border-b border-surface-variant/30 bg-surface/80 backdrop-blur-lg shrink-0">
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-md">
                    <span class="material-symbols-outlined text-white">auto_awesome</span>
                </div>
                <div class="flex-1">
                    <h2 class="font-headline text-headline-md text-on-surface text-[18px]">WanderAI</h2>
                    <p class="font-label text-label-sm text-on-surface-variant">Asisten pintar untuk {{ $trip->title }}</p>
                </div>
                <a href="{{ route('trips.show', $trip) }}" class="btn-ghost py-2 px-3 text-label-sm">
                    <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali
                </a>
            </div>

            {{-- Messages Container --}}
            <div id="chat-messages" class="flex-1 overflow-y-auto p-5 space-y-5">

                {{-- Welcome Message --}}
                <div class="flex gap-3" id="welcome-message">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center shrink-0 shadow-sm mt-1">
                        <span class="material-symbols-outlined text-white text-[16px]">auto_awesome</span>
                    </div>
                    <div class="flex-1 max-w-[80%]">
                        <div class="bg-surface-container rounded-2xl rounded-tl-md p-4 shadow-sm">
                            <p class="font-body text-body-md text-on-surface leading-relaxed">
                                Halo! 👋 Saya <strong class="text-primary">WanderAI</strong>, asisten perjalanan kamu.
                            </p>
                            <p class="font-body text-body-md text-on-surface-variant mt-2 leading-relaxed">
                                Saya siap membantu merencanakan trip <strong>{{ $trip->title }}</strong> ke <strong class="text-primary">{{ $trip->destination }}</strong>
                                bersama {{ $trip->members->count() }} orang.
                            </p>
                            <p class="font-body text-body-md text-on-surface-variant mt-2">Tanya apa saja tentang perjalanan kamu! 🗺️</p>
                        </div>

                        {{-- Smart Suggestions from trip state --}}
                        @if(count($suggestions) > 0)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($suggestions as $s)
                            <button onclick="sendMessage('{{ $s['text'] }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-tertiary-container/50 text-on-tertiary-container font-label text-label-sm hover:bg-tertiary-container transition-colors">
                                <span class="material-symbols-outlined text-[14px]">{{ $s['icon'] }}</span>
                                {{ $s['text'] }}
                            </button>
                            @endforeach
                        </div>
                        @endif

                        {{-- Quick Action Chips --}}
                        <div class="mt-3 flex flex-wrap gap-2" id="initial-suggestions">
                            @foreach(['Ringkasan trip', 'Rekomendasi makanan', 'Tips budget', 'Apa yang harus dibawa?'] as $q)
                            <button onclick="sendMessage('{{ $q }}')"
                                    class="chip bg-surface-container-high text-on-surface-variant hover:bg-primary hover:text-on-primary transition-colors text-[13px] px-3 py-1.5">
                                {{ $q }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Chat Input --}}
            <div class="p-4 border-t border-surface-variant/30 bg-surface/80 backdrop-blur-lg shrink-0">
                <form id="chat-form" onsubmit="event.preventDefault(); sendMessage()" class="flex gap-3">
                    <input type="text" id="chat-input"
                           class="flex-1 input-field pl-4 pr-4 py-3 rounded-xl text-body-md"
                           placeholder="Tanya WanderAI tentang trip kamu..."
                           autocomplete="off" maxlength="1000">
                    <button type="submit" id="send-btn"
                            class="w-12 h-12 rounded-xl bg-primary hover:bg-primary/90 text-on-primary flex items-center justify-center transition-colors shadow-md shrink-0 disabled:opacity-50"
                            disabled>
                        <span class="material-symbols-outlined">send</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Right Sidebar — Trip Info --}}
        <aside class="w-full xl:w-80 shrink-0 flex flex-col gap-stack-md">
            {{-- Trip Quick Stats --}}
            <div class="card">
                <h3 class="font-label text-label-md text-on-surface font-bold mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-primary">info</span>
                    Info Trip
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="font-label text-label-sm text-on-surface-variant">Destinasi</span>
                        <span class="font-label text-label-sm text-on-surface font-medium">{{ $trip->destination }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-label text-label-sm text-on-surface-variant">Tanggal</span>
                        <span class="font-label text-label-sm text-on-surface font-medium">{{ $trip->start_date->format('d M') }} — {{ $trip->end_date->format('d M') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-label text-label-sm text-on-surface-variant">Durasi</span>
                        <span class="font-label text-label-sm text-on-surface font-medium">{{ $trip->start_date->diffInDays($trip->end_date) }} hari</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-label text-label-sm text-on-surface-variant">Budget</span>
                        <span class="font-label text-label-sm text-primary font-medium">{{ rupiah($trip->budget ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-label text-label-sm text-on-surface-variant">Anggota</span>
                        <span class="font-label text-label-sm text-on-surface font-medium">{{ $trip->members->count() }} orang</span>
                    </div>
                </div>
            </div>

            {{-- Popular Topics --}}
            <div class="card">
                <h3 class="font-label text-label-md text-on-surface font-bold mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-secondary">lightbulb</span>
                    Topik Populer
                </h3>
                <div class="flex flex-col gap-2">
                    @foreach([
                        ['icon' => 'restaurant', 'text' => 'Rekomendasi kuliner', 'q' => 'Rekomendasi makanan'],
                        ['icon' => 'wb_sunny', 'text' => 'Info cuaca', 'q' => 'Bagaimana cuaca di sana?'],
                        ['icon' => 'directions_car', 'text' => 'Tips transportasi', 'q' => 'Tips transportasi'],
                        ['icon' => 'hiking', 'text' => 'Aktivitas seru', 'q' => 'Aktivitas apa yang bisa dilakukan?'],
                        ['icon' => 'luggage', 'text' => 'Packing list', 'q' => 'Apa yang harus dibawa?'],
                        ['icon' => 'account_balance_wallet', 'text' => 'Budget analysis', 'q' => 'Berapa biaya yang sudah dikeluarkan?'],
                    ] as $topic)
                    <button onclick="sendMessage('{{ $topic['q'] }}')"
                            class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-surface-container transition-colors text-left w-full group">
                        <span class="material-symbols-outlined text-[18px] text-outline group-hover:text-primary transition-colors">{{ $topic['icon'] }}</span>
                        <span class="font-label text-label-sm text-on-surface-variant group-hover:text-on-surface transition-colors">{{ $topic['text'] }}</span>
                    </button>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

    {{-- Chat JavaScript --}}
    <script>
        const chatInput = document.getElementById('chat-input');
        const sendBtn = document.getElementById('send-btn');
        const chatMessages = document.getElementById('chat-messages');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const chatUrl = "{{ route('trips.ai.chat', $trip) }}";

        // Enable/disable send button
        chatInput.addEventListener('input', () => {
            sendBtn.disabled = chatInput.value.trim().length === 0;
        });

        function sendMessage(text) {
            const message = text || chatInput.value.trim();
            if (!message) return;

            // Clear input
            chatInput.value = '';
            sendBtn.disabled = true;

            // Add user message bubble
            appendMessage('user', message);

            // Show typing indicator
            const typingId = showTyping();

            // Send to server
            fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message })
            })
            .then(r => r.json())
            .then(data => {
                removeTyping(typingId);
                appendMessage('ai', data.reply, data.suggestions);
            })
            .catch(() => {
                removeTyping(typingId);
                appendMessage('ai', '❌ Maaf, terjadi kesalahan. Coba lagi nanti.');
            });
        }

        function appendMessage(type, text, suggestions = []) {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex gap-3 animate-fade-in ' + (type === 'user' ? 'justify-end' : '');

            if (type === 'ai') {
                wrapper.innerHTML = `
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center shrink-0 shadow-sm mt-1">
                        <span class="material-symbols-outlined text-white text-[16px]">auto_awesome</span>
                    </div>
                    <div class="flex-1 max-w-[80%]">
                        <div class="bg-surface-container rounded-2xl rounded-tl-md p-4 shadow-sm">
                            <div class="font-body text-body-md text-on-surface leading-relaxed prose-sm">${formatMarkdown(text)}</div>
                        </div>
                        ${suggestions.length ? `<div class="mt-2 flex flex-wrap gap-2">${suggestions.map(s =>
                            `<button onclick="sendMessage('${s}')" class="chip bg-surface-container-high text-on-surface-variant hover:bg-primary hover:text-on-primary transition-colors text-[12px] px-2.5 py-1">${s}</button>`
                        ).join('')}</div>` : ''}
                    </div>
                `;
            } else {
                wrapper.innerHTML = `
                    <div class="max-w-[80%]">
                        <div class="bg-primary text-on-primary rounded-2xl rounded-tr-md px-4 py-3 shadow-sm">
                            <p class="font-body text-body-md leading-relaxed">${escapeHtml(text)}</p>
                        </div>
                    </div>
                `;
            }

            chatMessages.appendChild(wrapper);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showTyping() {
            const id = 'typing-' + Date.now();
            const el = document.createElement('div');
            el.id = id;
            el.className = 'flex gap-3';
            el.innerHTML = `
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center shrink-0 shadow-sm mt-1">
                    <span class="material-symbols-outlined text-white text-[16px]">auto_awesome</span>
                </div>
                <div class="bg-surface-container rounded-2xl rounded-tl-md px-5 py-3.5 shadow-sm">
                    <div class="flex gap-1.5 items-center">
                        <span class="w-2 h-2 rounded-full bg-on-surface-variant/40 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 rounded-full bg-on-surface-variant/40 animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-2 h-2 rounded-full bg-on-surface-variant/40 animate-bounce" style="animation-delay:300ms"></span>
                    </div>
                </div>
            `;
            chatMessages.appendChild(el);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return id;
        }

        function removeTyping(id) {
            document.getElementById(id)?.remove();
        }

        function formatMarkdown(text) {
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/^• (.*)/gm, '<div class="flex gap-2 items-start mt-1"><span class="text-primary shrink-0">•</span><span>$1</span></div>')
                .replace(/\n/g, '<br>');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Focus input on load
        chatInput.focus();
    </script>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>
</x-app-layout>
