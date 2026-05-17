<x-app-layout>
    @php $title = 'Edit - ' . $trip->title; @endphp

    <header class="mb-stack-lg flex justify-between items-end">
        <div>
            <h1 class="font-display text-display-lg text-on-background mb-1">Edit Trip</h1>
            <p class="font-body text-body-lg text-on-surface-variant">Update trip details for {{ $trip->title }}.</p>
        </div>
        <a href="{{ route('trips.show', $trip) }}" class="btn-ghost">
            <span class="material-symbols-outlined">arrow_back</span> Kembali
        </a>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-gutter">
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('trips.update', $trip) }}" enctype="multipart/form-data" class="card space-y-6">
                @csrf @method('PUT')

                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Nama Trip *</label>
                    <input class="input-field pl-4" name="title" type="text" value="{{ old('title', $trip->title) }}" required>
                    @error('title') <span class="text-error text-label-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Destinasi *</label>
                    <input class="input-field pl-4" name="destination" type="text" value="{{ old('destination', $trip->destination) }}" required>
                    @error('destination') <span class="text-error text-label-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Deskripsi</label>
                    <textarea class="input-field pl-4 resize-none" name="description" rows="4" placeholder="Ceritakan tentang trip ini...">{{ old('description', $trip->description) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Tanggal Mulai *</label>
                        <input class="input-field pl-4" name="start_date" type="date" value="{{ old('start_date', $trip->start_date->format('Y-m-d')) }}" required>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Tanggal Selesai *</label>
                        <input class="input-field pl-4" name="end_date" type="date" value="{{ old('end_date', $trip->end_date->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Budget</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant font-label">Rp</span>
                            <input class="input-field pl-10" name="budget" type="number" step="1" value="{{ old('budget', (int) $trip->budget) }}" placeholder="0">
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Status</label>
                        <select class="input-field pl-4" name="status">
                            @foreach(['planning' => 'Perencanaan', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $val => $label)
                            <option value="{{ $val }}" {{ $trip->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Cover Image</label>
                    @if($trip->cover_image)
                    <div class="w-full h-32 rounded-xl overflow-hidden mb-2">
                        <img src="{{ str_starts_with($trip->cover_image, 'http') ? $trip->cover_image : asset('storage/' . $trip->cover_image) }}" class="w-full h-full object-cover" alt="Cover">
                    </div>
                    @endif
                    <input class="input-field pl-4 py-2 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:bg-primary/10 file:text-primary" name="cover_image" type="file" accept="image/*">
                </div>

                <div class="flex gap-3 justify-end pt-4 border-t border-surface-variant/50">
                    <a href="{{ route('trips.show', $trip) }}" class="btn-ghost">Batal</a>
                    <button type="submit" class="btn-primary">
                        <span class="material-symbols-outlined">save</span> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        {{-- Danger Zone --}}
        <div>
            <div class="card border border-error/20">
                <h3 class="font-label text-label-md text-error font-bold mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">warning</span>
                    Zona Berbahaya
                </h3>
                <p class="font-body text-body-md text-on-surface-variant mb-4">
                    Menghapus trip akan menghapus semua data terkait termasuk itinerary, pengeluaran, dan voting.
                </p>
                <form method="POST" action="{{ route('trips.destroy', $trip) }}" onsubmit="return confirm('Yakin ingin menghapus trip ini? Semua data akan hilang.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl border border-error text-error hover:bg-error hover:text-on-error transition-colors font-label text-label-md flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">delete_forever</span>
                        Hapus Trip Ini
                    </button>
                </form>
            </div>

            {{-- Members Management --}}
            <div class="card mt-4">
                <h3 class="font-label text-label-md text-on-surface font-bold mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">group</span>
                    Anggota ({{ $trip->members->count() }})
                </h3>
                <div class="flex flex-col gap-3">
                    @foreach($trip->members as $member)
                    <div class="flex items-center gap-3 p-2 rounded-lg {{ $member->id === $trip->created_by ? 'bg-tertiary-container/10' : 'hover:bg-surface-container-low' }} transition-colors">
                        <div class="w-9 h-9 rounded-full {{ $member->id === $trip->created_by ? 'bg-tertiary/10 text-tertiary' : 'bg-surface-variant text-on-surface-variant' }} flex items-center justify-center font-bold text-[11px]">{{ $member->initials }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-label text-label-sm text-on-surface truncate">
                                {{ $member->name }}
                                @if($member->id === Auth::id())
                                <span class="text-primary text-[10px]">(You)</span>
                                @endif
                            </p>
                            <p class="font-label text-[11px] text-outline capitalize flex items-center gap-1">
                                @if($member->id === $trip->created_by)
                                <span class="material-symbols-outlined text-tertiary text-[12px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                Creator
                                @else
                                {{ $member->pivot->role }}
                                @endif
                            </p>
                        </div>

                        @if($member->id !== $trip->created_by)
                        <div class="flex items-center gap-1">
                            {{-- Role Toggle --}}
                            <form method="POST" action="{{ route('trips.members.role', [$trip, $member]) }}">
                                @csrf
                                @if($member->pivot->role === 'member')
                                <input type="hidden" name="role" value="organizer">
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-primary/10 text-outline hover:text-primary transition-colors" title="Promote to Organizer">
                                    <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                                </button>
                                @else
                                <input type="hidden" name="role" value="member">
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-outline/10 text-outline transition-colors" title="Demote to Member">
                                    <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                                </button>
                                @endif
                            </form>

                            {{-- Remove Member --}}
                            @if($member->id !== Auth::id())
                            <form method="POST" action="{{ route('trips.members.remove', [$trip, $member]) }}" onsubmit="return confirm('Remove {{ $member->name }} from this trip?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg hover:bg-error/10 text-outline hover:text-error transition-colors" title="Remove Member">
                                    <span class="material-symbols-outlined text-[16px]">person_remove</span>
                                </button>
                            </form>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
