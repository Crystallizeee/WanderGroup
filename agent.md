# Agent Guidelines for Wander (TripSync)

Halo Agent! Dokumen ini berisi aturan main untuk kamu saat memodifikasi codebase **Wander**. Harap patuhi aturan ini agar kualitas kode dan UX tetap terjaga.

## 🎯 Development Principles
1.  **Premium UX First**: Setiap UI baru harus terlihat premium. Hindari warna standar (plain red/blue). Gunakan token warna dari `DESIGN.md` dan `tailwind.config.js`.
2.  **Elevation & Depth**: Gunakan shadow Level 1 (default cards), Level 2 (hover), dan Level 3 (modals) sesuai spesifikasi desain.
3.  **Blade & Alpine.js**: Gunakan Alpine.js untuk interaksi ringan (modal, dropdown, inline-edit). Hindari penggunaan library JS berat kecuali sangat diperlukan.
4.  **Responsive**: Pastikan setiap fitur berjalan baik di mobile menggunakan grid 4-kolom dan margin 16px.

## 💻 Coding Standards
- **Naming**: Gunakan CamelCase untuk Controller/Model, snake_case untuk variabel dan kolom DB.
- **Activity Logging**: Setiap action yang mengubah data (Store/Update/Delete) **HARUS** mencatat log menggunakan `ActivityLog::log()`.
- **Validation**: Selalu validasi request di level Controller menggunakan `$request->validate()`.
- **Ownership Guard**: Untuk fitur Personal Packing, selalu cek `$item->assigned_to === Auth::id()` sebelum mengizinkan toggle/delete. Item shared boleh diubah siapa saja di grup.

## ✅ Checklist & Packing Rules
- Checklist terbagi 2 jenis: **Shared Items** (satu untuk grup) dan **Personal Items** (per orang).
- Jika menambahkan Personal Item dengan assign "Everyone", controller harus **menduplikasi item** untuk setiap member trip.
- **Smart Duplicate Detection** hanya berlaku untuk Shared Items (`is_shared = true`). Personal items yang bernama sama antar member adalah hal normal dan TIDAK boleh ditandai duplikat.
- UI Personal Packing dikelompokkan **per member** (bukan per kategori), lengkap dengan progress bar.
- Checkbox personal item hanya bisa diklik oleh pemiliknya. Member lain melihat checkbox **read-only** (disabled + ikon lock).

## 🗓 Itinerary Card Variants
Timeline itinerary menggunakan 3 varian card otomatis:
1. **Voting Card** (`status = voting`): Border dashed, background cerah, tombol "Go to Poll".
2. **Image Card** (`image_url` ada): Gambar di sisi kiri, info di kanan, ikon map jika ada koordinat.
3. **Standard Card** (default): Judul, deskripsi, avatar anggota.

## 🤖 AI & API Integration
Proyek ini mendukung integrasi dengan model AI canggih (Gemini, OpenRouter).

### API Configuration
> [!CAUTION]
> Jangan pernah menulis API Key langsung di dalam kode atau file markdown ini. Gunakan file `.env`.

**Required Keys in `.env`:**
- `OPENROUTER_API_KEY`: Untuk akses model via OpenRouter.
- `GEMINI_API_KEY`: Untuk akses Google Generative AI.
- `UNSPLASH_ACCESS_KEY`: Untuk fetch gambar otomatis pada Itinerary Item.

### Priority Models
Jika diminta melakukan integrasi AI, prioritaskan model berikut:
- **Gemini 2.5/3 Flash**: Untuk tugas cepat, scanning struk (OCR), dan chatbot rutin.
- **Gemini 2.5/3 Pro**: Untuk perencanaan rute yang kompleks dan analisis data berat.
- **OpenRouter (Auto)**: Sebagai fallback jika API utama mengalami limit.

## 📂 Important Paths
- **Views**: `resources/views/trips/*.blade.php` (Fitur utama).
- **Layouts**: `resources/views/layouts/app.blade.php` (Navigation).
- **Design Docs**: `Design/collaborative_travel_design_system/DESIGN.md`.

## ⚠️ Critical Checks
- **Trip Context**: Pastikan `$trip` selalu dilewatkan ke view dan diproteksi middleware `EnsureTripMember`.
- **Invite Code**: Gunakan `Trip::getInviteCode()` untuk integrasi link undangan.