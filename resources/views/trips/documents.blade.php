<x-app-layout>
    @php $title = $trip->title . ' - Document Vault'; @endphp

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-stack-lg gap-stack-sm">
        <div>
            <h2 class="font-headline-lg-mobile md:font-display-lg text-headline-lg-mobile md:text-display-lg text-on-surface">Document Vault</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-unit">Securely store and access your essential travel documents.</p>
        </div>
        <div class="flex items-center gap-stack-sm w-full md:w-auto">
            <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="flex-shrink-0 flex items-center justify-center gap-2 bg-primary text-on-primary py-2 px-4 rounded-full font-label-md text-label-md hover:shadow-[0px_8px_30px_rgba(0,0,0,0.08)] transition-all">
                <span class="material-symbols-outlined text-[18px]">upload</span>
                <span class="hidden sm:inline">Upload New</span>
            </button>
        </div>
    </div>

    <!-- Layout Grid for Content & Preview -->
    <div class="flex flex-col lg:flex-row gap-gutter" x-data="{
            selectedDoc: null,
            previewDoc(doc) {
                this.selectedDoc = doc;
            }
        }">
        <!-- Document List Area (Left Side) -->
        <div class="flex-1 flex flex-col gap-stack-md">
            @if($documents->isEmpty())
                <div class="bg-surface-bright rounded-xl border-2 border-dashed border-outline-variant p-12 flex flex-col items-center justify-center text-center">
                    <span class="material-symbols-outlined text-[48px] text-primary/30 mb-4">folder_open</span>
                    <h4 class="font-headline text-headline-md text-on-surface mb-2">No documents yet</h4>
                    <p class="font-body text-body-md text-on-surface-variant mb-4">Upload flight tickets, hotel vouchers, and other important files.</p>
                    <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="btn-secondary">
                        <span class="material-symbols-outlined">upload</span> Upload First Document
                    </button>
                </div>
            @else
                <!-- Document Grid (Bento Style) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-stack-md">
                    @foreach($documents as $doc)
                    <div @click="previewDoc({{ json_encode([
                            'id' => $doc->id,
                            'title' => $doc->title,
                            'file_type' => $doc->file_type,
                            'size' => $doc->file_size > 0 ? number_format($doc->file_size / 1048576, 2) . ' MB' : 'Google Drive',
                            'date' => $doc->created_at->format('M d, Y'),
                            'url' => $doc->url,
                            'preview_url' => $doc->preview_url,
                            'download_url' => route('trips.documents.download', [$trip, $doc]),
                            'delete_url' => route('trips.documents.destroy', [$trip, $doc]),
                            'can_delete' => ($doc->user_id === auth()->id() || $trip->organizers->contains(auth()->user())),
                        ]) }})"
                        class="bg-surface-container-lowest rounded-xl p-[20px] shadow-[0px_4px_20px_rgba(0,0,0,0.04)] hover:shadow-[0px_8px_30px_rgba(0,0,0,0.08)] transition-all border border-transparent hover:border-surface-variant group cursor-pointer flex flex-col justify-between min-h-[160px]"
                        :class="{ 'ring-2 ring-primary': selectedDoc && selectedDoc.id === {{ $doc->id }} }">
                        <div class="flex justify-between items-start">
                            <div class="p-2 rounded-lg 
                                {{ match($doc->category) {
                                    'transport' => 'bg-primary-container/20 text-primary',
                                    'accommodation' => 'bg-tertiary-container/20 text-tertiary',
                                    'logistics' => 'bg-error-container/40 text-error',
                                    'photos_media' => 'bg-blue-500/20 text-blue-500',
                                    default => 'bg-secondary-container/20 text-secondary'
                                } }}">
                                <span class="material-symbols-outlined">{{ match($doc->category) {
                                    'transport' => 'flight',
                                    'accommodation' => 'hotel',
                                    'logistics' => 'health_and_safety',
                                    'photos_media' => 'photo_library',
                                    default => 'description'
                                } }}</span>
                            </div>
                        </div>
                        <div class="mt-stack-sm">
                            <h3 class="font-headline-md text-headline-md text-on-surface truncate" title="{{ $doc->title }}">{{ $doc->title }}</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant mt-unit">Added {{ $doc->created_at->diffForHumans() }} • {{ $doc->file_size > 0 ? number_format($doc->file_size / 1048576, 2) . ' MB' : 'Google Drive' }}</p>
                        </div>
                        <div class="mt-stack-md flex gap-2">
                            <span class="inline-block px-2 py-0.5 rounded-full capitalize 
                                {{ match($doc->category) {
                                    'transport' => 'bg-primary-container/30 text-primary',
                                    'accommodation' => 'bg-tertiary-container/30 text-tertiary',
                                    'logistics' => 'bg-error-container/50 text-on-error-container',
                                    'photos_media' => 'bg-blue-500/20 text-blue-600',
                                    default => 'bg-secondary-container/30 text-secondary'
                                } }} font-label-sm text-label-sm">{{ str_replace('_', ' ', $doc->category) }}</span>
                            <span class="inline-block px-2 py-0.5 rounded-full font-label-sm text-label-sm uppercase"
                                :class="'{{ $doc->file_type }}' === 'drive' ? 'bg-[#e8f0fe] text-[#1967d2]' : 'bg-surface-variant text-on-surface-variant'">{{ $doc->file_type }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Preview Pane (Right Side) -->
        <div x-show="selectedDoc" style="display: none;" class="w-full lg:w-96 bg-surface-container-lowest rounded-xl shadow-[0px_12px_40px_rgba(0,42,124,0.12)] border border-outline-variant flex flex-col overflow-hidden h-fit sticky top-[100px]">
            <div class="p-stack-md border-b border-surface-variant flex justify-between items-center bg-surface-bright">
                <h3 class="font-headline-md text-headline-md text-on-surface">Quick View</h3>
                <div class="flex gap-2">
                    <template x-if="selectedDoc.file_type !== 'drive'">
                        <a :href="selectedDoc.download_url" aria-label="Download" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container rounded-md transition-colors">
                            <span class="material-symbols-outlined text-[20px]">download</span>
                        </a>
                    </template>
                    <template x-if="selectedDoc.file_type === 'drive'">
                        <a :href="selectedDoc.url" target="_blank" aria-label="Open in Drive" class="p-1.5 text-[#1967d2] hover:bg-[#e8f0fe] rounded-md transition-colors" title="Open Google Drive">
                            <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                        </a>
                    </template>
                    <form x-show="selectedDoc.can_delete" :action="selectedDoc.delete_url" method="POST" onsubmit="return confirm('Delete this document?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" aria-label="Delete" class="p-1.5 text-on-surface-variant hover:text-error hover:bg-error-container/50 rounded-md transition-colors">
                            <span class="material-symbols-outlined text-[20px]">delete</span>
                        </button>
                    </form>
                </div>
            </div>
            <div class="p-stack-md flex-1 flex flex-col">
                <div class="w-full aspect-[3/4] bg-surface-container rounded-lg border border-outline-variant mb-stack-md overflow-hidden relative group">
                    <template x-if="selectedDoc.preview_url && selectedDoc.preview_url.includes('drive.google.com')">
                        <iframe :src="selectedDoc.preview_url" class="absolute inset-0 w-full h-full border-0" allow="autoplay"></iframe>
                    </template>
                    <template x-if="(!selectedDoc.preview_url || !selectedDoc.preview_url.includes('drive.google.com')) && selectedDoc.file_type === 'image'">
                        <img :src="selectedDoc.url" :alt="selectedDoc.title" class="absolute inset-0 w-full h-full object-contain">
                    </template>
                    <template x-if="(!selectedDoc.preview_url || !selectedDoc.preview_url.includes('drive.google.com')) && selectedDoc.file_type !== 'image'">
                        <div class="absolute inset-0 flex items-center justify-center bg-surface-dim">
                            <span class="material-symbols-outlined text-[64px] text-outline-variant">description</span>
                        </div>
                    </template>
                </div>
                <div class="space-y-stack-sm">
                    <h4 class="font-headline-md text-headline-md text-on-surface break-words" x-text="selectedDoc.title"></h4>
                    <div class="grid grid-cols-2 gap-y-2">
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Type</span>
                        <span class="font-body-md text-body-md text-on-surface text-right uppercase" x-text="selectedDoc.file_type"></span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Size</span>
                        <span class="font-body-md text-body-md text-on-surface text-right" x-text="selectedDoc.size"></span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant">Uploaded</span>
                        <span class="font-body-md text-body-md text-on-surface text-right" x-text="selectedDoc.date"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" x-data="{ tab: 'local' }">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="relative bg-surface-container-lowest rounded-2xl shadow-elevation-3 w-full max-w-md p-0 overflow-hidden flex flex-col">
            <div class="flex justify-between items-center p-6 pb-4">
                <h3 class="font-headline text-headline-md text-on-surface">Add Document or Media</h3>
                <button onclick="this.closest('#upload-modal').classList.add('hidden')" class="text-outline hover:text-primary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="flex border-b border-outline-variant px-6 gap-4">
                <button @click="tab = 'local'" :class="tab === 'local' ? 'text-primary border-b-2 border-primary' : 'text-on-surface-variant border-b-2 border-transparent hover:text-on-surface'" class="pb-2 font-label-md transition-colors">Local File</button>
                <button @click="tab = 'drive'" :class="tab === 'drive' ? 'text-[#1976d2] border-b-2 border-[#1976d2]' : 'text-on-surface-variant border-b-2 border-transparent hover:text-on-surface'" class="pb-2 font-label-md transition-colors flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">add_to_drive</span> Google Drive</button>
            </div>

            <form method="POST" action="{{ route('trips.documents.store', $trip) }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="upload_type" :value="tab">
                
                <!-- Local Upload -->
                <div x-show="tab === 'local'" class="flex flex-col gap-2 transition-all">
                    <label class="font-label text-label-md text-on-surface">File *</label>
                    <input class="input-field pl-4 py-2 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:bg-primary/10 file:text-primary" name="document" type="file" :required="tab === 'local'">
                </div>

                <!-- Drive Upload -->
                <div x-show="tab === 'drive'" style="display: none;" class="flex flex-col gap-4 transition-all">
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Drive Link URL *</label>
                        <input type="url" name="drive_url" class="input-field pl-4 py-2" placeholder="https://drive.google.com/..." :required="tab === 'drive'">
                        <p class="text-[12px] text-on-surface-variant">Paste the link of your shared Google Drive folder or file.</p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="font-label text-label-md text-on-surface">Title *</label>
                        <input type="text" name="drive_title" class="input-field pl-4 py-2" placeholder="e.g. Bali Trip Photos" :required="tab === 'drive'">
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label class="font-label text-label-md text-on-surface">Category *</label>
                    <select class="input-field pl-4" name="category" required>
                        <option value="photos_media">Photos & Media</option>
                        <option value="transport">Transport (Flight, Train, etc.)</option>
                        <option value="accommodation">Accommodation</option>
                        <option value="logistics">Logistics (Insurance, Visa)</option>
                        <option value="general">General</option>
                    </select>
                </div>
                
                <button type="submit" class="w-full mt-2 rounded-full py-2.5 font-label-md transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2" :class="tab === 'drive' ? 'bg-[#1976d2] text-white hover:bg-[#1565c0]' : 'bg-primary text-on-primary hover:bg-primary/90'">
                    <span class="material-symbols-outlined" x-text="tab === 'drive' ? 'add_to_drive' : 'upload'"></span>
                    <span x-text="tab === 'drive' ? 'Save Drive Link' : 'Upload'"></span>
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
