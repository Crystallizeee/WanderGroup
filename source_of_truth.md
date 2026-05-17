# TripSync (WanderGroup) - Source of Truth

## 🌟 Project Overview
TripSync (internal name: **WanderGroup**) adalah platform kolaboratif untuk merencanakan perjalanan grup. Aplikasi ini memungkinkan pengguna untuk membuat rencana perjalanan (itinerary), mencatat pengeluaran bersama (finances), menyimpan dokumen perjalanan, dan melakukan voting untuk pengambilan keputusan grup.

## 🛠 Tech Stack
- **Core**: PHP 8.2+ / Laravel 11
- **Frontend**: Blade Templates, Tailwind CSS (Custom Theme), Alpine.js
- **Database**: SQLite (Local)
- **State Management**: Alpine.js (Lightweight reactive UI)
- **Bundler**: Vite
- **Libraries**:
    - **Leaflet.js**: Untuk visualisasi peta di Itinerary.
    - **Chart.js**: Untuk visualisasi statistik keuangan.
    - **Material Symbols**: Untuk ikonografi sistem.

## 🏗 Database Architecture
- `trips`: Data utama perjalanan (judul, destinasi, tanggal, budget, `invite_code`).
- `trip_user`: Pivot table untuk anggota grup (Role: `owner`, `organizer`, `member`).
- `itinerary_days` & `itinerary_items`: Struktur timeline perjalanan (support koordinat lat/lng, `image_url` via Unsplash API, `type`, `status`).
- `expenses` & `expense_splits`: Sistem pencatatan hutang-piutang grup.
- `settlements`: Record pelunasan hutang antar member.
- `polls`, `poll_options`, `votes`: Sistem voting grup (type: `single`/`multiple`).
- `checklist_items`: Packing checklist dengan field `is_shared` (shared vs personal), `assigned_to`, `is_checked`, `quantity`.
- `trip_invitations`: Sistem undangan berbasis email dan token.
- `activity_logs`: Audit trail untuk setiap perubahan yang dilakukan anggota grup.
- `documents`: Document Vault untuk file perjalanan.

## 🚀 Key Features Implemented
1.  **Smart Onboarding**: Bergabung ke trip otomatis via Link/Invite Code.
2.  **Interactive Itinerary**: Timeline harian dengan 3 varian card otomatis (Voting, Image, Standard). Auto-fetch gambar dari Unsplash API. Auto-create Poll saat status diubah ke `voting`.
3.  **Financial Dashboard**: Grafik kategori pengeluaran (Doughnut Chart) dan AI Receipt Scanning.
4.  **Debt Settlement**: Perhitungan saldo antar anggota grup (siapa berhutang ke siapa) dengan optimized settlement algorithm.
5.  **Group Voting**: Polling dengan opsi single/multiple choice. Integrasi otomatis dari Itinerary (status `voting`).
6.  **Checklist & Packing**:
    - **Shared Items**: Dikelompokkan per kategori (Essentials, Shared Gear, dll). Smart Duplicate Detection untuk barang yang sama.
    - **Personal Packing**: Dikelompokkan per member dengan progress bar individual. Ownership guard (hanya pemilik bisa centang). Auto-duplicate ke semua member jika assign "Everyone".
    - **AI Suggestions**: Rekomendasi barang bawaan berdasarkan destinasi dan cuaca.
7.  **Document Vault**: Upload dan download dokumen perjalanan.
8.  **WanderAI Chat**: Chatbot AI untuk konsultasi perjalanan.

## 🎨 Design System
- **Theme**: Premium Modern (Plus Jakarta Sans & Inter).
- **Colors**: Menggunakan Material 3 naming convention (Primary: `#00658d`, Surface, On-Surface, Tertiary: `#8d4f00`).
- **Layout**: Fixed sidebar di desktop, Floating glass bottom nav di mobile.
- **Card Variants**: Itinerary menggunakan 3 layout otomatis (Voting/dashed, Image/horizontal, Standard).
- **Checklist Layout**: Bento Grid untuk shared items, Per-member cards dengan progress untuk personal items.
- **Design References**: Tersedia di `Design/` folder (HTML mockups dari desain high-fidelity).
