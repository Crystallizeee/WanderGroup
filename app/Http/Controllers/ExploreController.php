<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExploreController extends Controller
{
    /**
     * Show the explore/discover page with popular destinations.
     */
    public function index()
    {
        $destinations = collect([
            [
                'name' => 'Bali, Indonesia',
                'tagline' => 'Island of the Gods',
                'description' => 'Pristine beaches, ancient temples, lush rice terraces, and world-class surfing.',
                'emoji' => '🏝️',
                'gradient' => 'from-[#00b4d8] to-[#0077b6]',
                'tags' => ['Beach', 'Culture', 'Surf', 'Wellness'],
                'avg_budget' => 5000000,
                'best_months' => 'Apr - Oct',
                'trips_planned' => 4250,
            ],
            [
                'name' => 'Tokyo, Japan',
                'tagline' => 'Where Tradition Meets Future',
                'description' => 'Neon-lit streets, ancient shrines, incredible food, and cutting-edge technology.',
                'emoji' => '🗼',
                'gradient' => 'from-[#e63946] to-[#a8dadc]',
                'tags' => ['City', 'Food', 'Culture', 'Tech'],
                'avg_budget' => 15000000,
                'best_months' => 'Mar - May, Sep - Nov',
                'trips_planned' => 3800,
            ],
            [
                'name' => 'Rome, Italy',
                'tagline' => 'The Eternal City',
                'description' => 'Colosseum, Vatican, pasta, and gelato — 3000 years of history in every corner.',
                'emoji' => '🏛️',
                'gradient' => 'from-[#d4a373] to-[#588157]',
                'tags' => ['History', 'Food', 'Art', 'Romance'],
                'avg_budget' => 20000000,
                'best_months' => 'Apr - Jun, Sep - Oct',
                'trips_planned' => 5100,
            ],
            [
                'name' => 'Banff, Canada',
                'tagline' => 'Rocky Mountain Paradise',
                'description' => 'Turquoise lakes, snow-capped peaks, wildlife, and endless hiking trails.',
                'emoji' => '🏔️',
                'gradient' => 'from-[#2d6a4f] to-[#52b788]',
                'tags' => ['Nature', 'Hiking', 'Ski', 'Adventure'],
                'avg_budget' => 25000000,
                'best_months' => 'Jun - Sep, Dec - Mar',
                'trips_planned' => 2200,
            ],
            [
                'name' => 'Barcelona, Spain',
                'tagline' => 'Gaudí, Tapas & Sunshine',
                'description' => 'Stunning architecture, vibrant nightlife, Mediterranean beaches, and tapas crawls.',
                'emoji' => '🌅',
                'gradient' => 'from-[#f4845f] to-[#ffd166]',
                'tags' => ['Beach', 'Nightlife', 'Art', 'Food'],
                'avg_budget' => 18000000,
                'best_months' => 'May - Sep',
                'trips_planned' => 3400,
            ],
            [
                'name' => 'Marrakech, Morocco',
                'tagline' => 'Sensory Overload',
                'description' => 'Bustling souks, aromatic spice markets, riads, and the Sahara just hours away.',
                'emoji' => '🕌',
                'gradient' => 'from-[#bc6c25] to-[#606c38]',
                'tags' => ['Culture', 'Adventure', 'Food', 'Desert'],
                'avg_budget' => 8000000,
                'best_months' => 'Mar - May, Sep - Nov',
                'trips_planned' => 1800,
            ],
        ]);

        $categories = [
            ['icon' => 'beach_access', 'name' => 'Beach', 'color' => 'primary'],
            ['icon' => 'landscape', 'name' => 'Mountain', 'color' => 'secondary'],
            ['icon' => 'location_city', 'name' => 'City', 'color' => 'tertiary'],
            ['icon' => 'temple_buddhist', 'name' => 'Culture', 'color' => 'primary'],
            ['icon' => 'restaurant', 'name' => 'Food', 'color' => 'secondary'],
            ['icon' => 'hiking', 'name' => 'Adventure', 'color' => 'tertiary'],
        ];

        return view('explore', compact('destinations', 'categories'));
    }
}
