# 🧭 WanderGroup — Smart Group Trip Planner & Expense Manager

<p align="center">
  <img src="public/images/hero_logo.png" width="160" alt="WanderGroup Logo" style="border-radius: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
</p>

<p align="center">
  <strong>WanderGroup</strong> is a stunning, collaborative group trip planning and expense management platform built using <strong>Laravel 13</strong>, <strong>Tailwind CSS</strong>, <strong>PostgreSQL</strong>, and <strong>Gemini AI</strong>. It simplifies collaborative group travel from itinerary mapping down to splitting the bills and settling interpersonal debts.
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13.8.0-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel 13"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.4.21-777BB4?style=for-the-badge&logo=php" alt="PHP 8"></a>
  <a href="https://postgresql.org"><img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?style=for-the-badge&logo=postgresql" alt="PostgreSQL"></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-3.4-38B2AC?style=for-the-badge&logo=tailwind-css" alt="Tailwind CSS"></a>
  <a href="https://docker.com"><img src="https://img.shields.io/badge/Docker-Enabled-2496ED?style=for-the-badge&logo=docker" alt="Docker"></a>
</p>

---

## ✨ Features Showcase

### 🗺️ Interactive Group Itinerary
* **Collaborative Days**: Map out your group's adventure day-by-day. Any member can add, edit, or reorder activities.
* **Interactive Leaflet Map Integration**: View your itinerary markers, routes, and geographic paths in real-time.
* **Custom Notes**: Keep check-in details, booking links, or contact numbers right where everyone can see them.

### 💰 Financial & Split Dashboard
* **Flexible Bill Splitting**: Add expenses and split them using standard methods:
  * **Split Equally**: Share bills perfectly across all members.
  * **By Percentage (%)**: Allocate distinct fractional values per person.
  * **Exact Amounts**: Record the exact rupiah spent per person.
  * **Advanced Itemized Splits**: List individual receipt items and assign who bought what.
* **Dynamic Paid-By Selector**: Select exactly who paid for each expense, both when adding new items or editing past logs.
* **AI Receipt Scanner**: Snap a picture of your physical receipt. Powered by **Gemini AI Vision**, it instantly extracts the merchant title, total amount, category, and individual line items (quantity, unit price, total price) automatically.

### 📊 Bento-Style Budgeting (Planned vs. Actual)
* **Visual Expense Tracking**: Beautiful, premium Bento grid showing trip-wide planned category budgets vs. actual spending.
* **Category Breakdown**: Keep tabs on Lodging, Food, Transport, Activities, and Misc separately.
* **Individual Budget Limits**: Allocate custom maximum spending limits per user to prevent travel group overspending.

### 💳 Debt Settlement Solver (Optimized Simplifying Algorithm)
* **Transaction Minimizer**: Uses an optimized Net-Balance transactional simplification algorithm (Debt Settlement Solver) to resolve interpersonal debts with the **minimum number of transactions**.
* **Settlement History**: Maintain a transparent timeline of recorded group payments.
* **Cancellation Safety (Undo/Delete)**: Made a mistake? Cancel a settlement with a single click. System automatically reverts settled tagihans back to unsettled, instantly updating everyone's balances.

### 🗳️ Decision-Making Group Polls
* **Real-time Voting**: Can't decide on dinner or lodging? Create beautiful interactive polls with custom options.
* **Organizer Management**: Trip organizers can securely close active polls once a decision has been reached.

### 🤖 WanderAI Smart Travel Companion
* **Native AI Travel Chat**: Integrated conversational assistant that knows your trip details and itinerary, giving you customized tourist recommendations, dining options, packing tips, or local customs in seconds.

### 📂 Collaborative Document Vault
* **Group Storage**: Store flight tickets, booking confirmations, hotel vouchers, and ID scans securely in one shared space. Download or delete files on the go.

### 👤 Bento-Style Travel Profiles
* **Dietary Preferences & Custom Tags**: Personalize dietary tags (e.g., Vegetarian, Gluten-Free) or other custom travel styles.
* **Travel Biography**: Beautiful profile card with interactive bio settings.

---

## 🛠️ Technology Stack

* **Backend Framework:** Laravel 13.8.0
* **Programming Language:** PHP 8.4.21 / 8.5.6
* **Database:** PostgreSQL 16
* **Frontend:** Tailwind CSS, Alpine.js, LeafletJS (Maps)
* **AI Engine:** Google Gemini API (using Gemini Flash Vision model for OCR receipt scanning and conversational travel tips)
* **Containerization:** Docker & Docker Compose

---

## 🚀 Quick Setup & Installation

### 1. Clone & Prepare Environment
```bash
git clone https://github.com/Crystallizeee/WanderGroup.git
cd WanderGroup/app
cp .env.example .env
```

### 2. Install Dependencies
```bash
composer install
npm install
npm run build # or npm run dev
```

### 3. Setup Database & Key Settings
Edit your `.env` file to configure your PostgreSQL credentials and Gemini API key:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=WanderGroup
DB_USERNAME=your_username
DB_PASSWORD=your_password

GEMINI_API_KEY=your_gemini_api_key
```

### 4. Run Migrations & Seed Database
Initialize your tables along with sample trips, user accounts, and test data:
```bash
php artisan migrate --seed
```

### 5. Start Development Server
```bash
php artisan serve
```
Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser!

---

## 🐳 Docker Production Deployment

WanderGroup is deployed on a secure VPS at `192.168.1.181` inside a Docker setup.

### Useful Production Commands:
```bash
# Pull changes from main
git pull origin main

# Sync files into Docker container
docker cp /var/www/WanderGroup/app/ wandergroup-app-1:/var/www/app

# Clear application cache inside container
docker exec wandergroup-app-1 php artisan config:clear
docker exec wandergroup-app-1 php artisan route:clear
docker exec wandergroup-app-1 php artisan view:clear
```

---

## 📄 License
This project is open-sourced software licensed under the [MIT license](LICENSE).
