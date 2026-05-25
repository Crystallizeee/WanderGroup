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
                ])->withoutVerifying()->get("https://nominatim.openstreetmap.org/reverse", [
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

        // 2. Download from Google Drive if URL is stored, otherwise fallback to local
        $tempLocalPath = null;
        $isTemporary = false;
        
        try {
            if (str_starts_with($memory->file_path, 'http://') || str_starts_with($memory->file_path, 'https://')) {
                $fileId = null;
                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $memory->file_path, $matches)) {
                    $fileId = $matches[1];
                } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $memory->file_path, $matches)) {
                    $fileId = $matches[1];
                }

                if ($fileId) {
                    $client = new \Google\Client();
                    $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
                    $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
                    $client->refreshToken(env('GOOGLE_DRIVE_REFRESH_TOKEN'));
                    $client->addScope(\Google\Service\Drive::DRIVE);
                    
                    $service = new \Google\Service\Drive($client);
                    $response = $service->files->get($fileId, ['alt' => 'media']);
                    $imageContent = $response->getBody()->getContents();
                    
                    $tempLocalPath = tempnam(sys_get_temp_dir(), 'gemini_');
                    file_put_contents($tempLocalPath, $imageContent);
                    $isTemporary = true;
                } else {
                    throw new \Exception("Could not extract Google Drive File ID from: " . $memory->file_path);
                }
            } else {
                $tempLocalPath = storage_path('app/public/' . $memory->file_path);
            }
        } catch (\Exception $e) {
            Log::error("Failed to read image for AI processing: " . $e->getMessage());
            $memory->update([
                'location' => $resolvedAddress ?? 'Lokasi tidak terdeteksi',
                'ai_tags' => [],
            ]);
            return;
        }

        // 3. Call Gemini AI to get tags and fallback location
        try {
            $aiService = new TripAIService();
            $result = $aiService->analyzeImage($tempLocalPath, $resolvedAddress);

            $memory->update([
                'location' => $result['location'] ?? $resolvedAddress ?? 'Lokasi tidak terdeteksi',
                'ai_tags' => $result['tags'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error("ProcessMemoryImage Job Exception: " . $e->getMessage());
            
            // Fallback so it doesn't show "Menganalisis lokasi..." forever
            $memory->update([
                'location' => $resolvedAddress ?? 'Lokasi tidak terdeteksi',
                'ai_tags' => [],
            ]);
        } finally {
            // Clean up temporary file
            if ($isTemporary && $tempLocalPath && file_exists($tempLocalPath)) {
                @unlink($tempLocalPath);
            }
        }
    }
}
