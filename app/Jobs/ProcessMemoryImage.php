<?php

namespace App\Jobs;

use App\Models\Memory;
use App\Services\TripAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessMemoryImage implements ShouldQueue
{
    use Queueable;

    protected $memoryId;
    protected $latitude;
    protected $longitude;
    protected $drivePath;

    /**
     * Create a new job instance.
     */
    public function __construct($memoryId, $latitude = null, $longitude = null, $drivePath = null)
    {
        $this->memoryId = $memoryId;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->drivePath = $drivePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $memory = Memory::find($this->memoryId);
        if (!$memory) {
            return;
        }

        $resolvedAddress = null;

        // 1. Try Reverse Geocoding via OpenStreetMap Nominatim if GPS is available
        if ($this->latitude !== null && $this->longitude !== null) {
            try {
                // Nominatim requires a user-agent header
                $response = Http::withHeaders([
                    'User-Agent' => 'WanderGroupApp/1.0 (trip-planner)'
                ])->get("https://nominatim.openstreetmap.org/reverse", [
                    'format' => 'json',
                    'lat' => $this->latitude,
                    'lon' => $this->longitude,
                    'zoom' => 10, // City level zoom
                    'addressdetails' => 1
                ]);

                if ($response->successful()) {
                    $address = $response->json('address');
                    if ($address) {
                        $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? null;
                        $country = $address['country'] ?? null;

                        if ($city && $country) {
                            $resolvedAddress = $city . ', ' . $country;
                        } elseif ($country) {
                            $resolvedAddress = $country;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Nominatim Reverse Geocode failed: " . $e->getMessage());
            }
        }

        // If the memory is a video, skip downloading and AI analysis completely
        if ($memory->isVideo()) {
            $memory->update([
                'location' => $resolvedAddress ?? 'Lokasi tidak terdeteksi',
                'ai_tags' => ['video'],
            ]);
            return;
        }

        // AI auto captioning / visual analysis is disabled as requested by the user.
        // We directly update the memory with the geocoded location and empty tags, bypassing Google Drive downloads and Gemini calls.
        $memory->update([
            'location' => $resolvedAddress ?? 'Lokasi tidak terdeteksi',
            'ai_tags' => [],
        ]);
    }
}
