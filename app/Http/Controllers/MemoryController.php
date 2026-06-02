<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Memory;
use App\Models\ActivityLog;
use App\Jobs\ProcessMemoryImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemoryController extends Controller
{
    /**
     * Display the memories gallery for the trip.
     */
    public function index(Trip $trip)
    {
        $memories = $trip->memories()
            ->with('user')
            ->orderByDesc('is_highlighted')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total' => $memories->count(),
            'members' => $memories->pluck('user_id')->unique()->count(),
            'highlighted' => $memories->where('is_highlighted', true)->count(),
            'locations' => $memories->whereNotIn('location', ['Menganalisis lokasi...', 'Lokasi tidak terdeteksi', null])->pluck('location')->unique()->count(),
        ];

        return view('trips.memories', compact('trip', 'memories', 'stats'));
    }

    /**
     * Store a newly uploaded memory.
     */
    public function store(Request $request, Trip $trip)
    {
        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['file', 'max:5242880', 'mimes:jpg,jpeg,png,gif,mp4,mov,avi,webm,qt,quicktime'], // max 5GB per file
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $files = $request->file('images');
        $uploadedCount = 0;

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'webm', 'qt', 'quicktime']);

            // 1. Extract EXIF data before compression/resizing (Images only)
            $takenAt = null;
            $latitude = null;
            $longitude = null;

            if (!$isVideo && in_array($extension, ['jpg', 'jpeg'])) {
                try {
                    $exif = @exif_read_data($file->getRealPath());
                    if ($exif) {
                        if (isset($exif['DateTimeOriginal'])) {
                            try {
                                $takenAt = \Carbon\Carbon::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
                            } catch (\Exception $e) {
                                $takenAt = null;
                            }
                        }
                        if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude']) && isset($exif['GPSLatitudeRef']) && isset($exif['GPSLongitudeRef'])) {
                            $latitude = self::getGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
                            $longitude = self::getGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore EXIF errors
                }
            }

            // 2. Setup path & directory for local temporary compression
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $filename = 'img_' . time() . '_' . Str::random(5) . '.' . $extension;
            $destination = $tempDir . '/' . $filename;

            // 3. Compress and Resize Image using GD (Target: Max 800px) - ONLY for non-videos!
            $compressed = false;
            if (!$isVideo) {
                try {
                    $tempPath = $file->getRealPath();
                    $src = null;
                    if ($extension === 'jpeg' || $extension === 'jpg') {
                        $src = @imagecreatefromjpeg($tempPath);
                    } elseif ($extension === 'png') {
                        $src = @imagecreatefrompng($tempPath);
                    }

                    if ($src) {
                    list($width, $height) = getimagesize($tempPath);
                    $maxDim = 800; // Resize to max 800px width/height
                    if ($width > $maxDim || $height > $maxDim) {
                        $ratio = $width / $height;
                        if ($ratio > 1) {
                            $newWidth = $maxDim;
                            $newHeight = (int)($maxDim / $ratio);
                        } else {
                            $newHeight = $maxDim;
                            $newWidth = (int)($maxDim * $ratio);
                        }
                        $dst = imagecreatetruecolor($newWidth, $newHeight);
                        if ($extension === 'png') {
                            imagealphablending($dst, false);
                            imagesavealpha($dst, true);
                        }
                        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        imagedestroy($src);
                        $src = $dst;
                    }

                    if ($extension === 'jpeg' || $extension === 'jpg') {
                        imagejpeg($src, $destination, 75); // 75% quality for small footprint
                        $compressed = true;
                    } elseif ($extension === 'png') {
                        imagepng($src, $destination, 6); // level 6 compression
                        $compressed = true;
                    }
                    imagedestroy($src);
                }
            } catch (\Exception $e) {
                // GD failed
            }
        }

            // 4. Upload to Google Drive with graceful local fallback if credentials failed
            $folderName = Str::slug($trip->title) . '_' . $trip->id;
            $driveFolder = $folderName . '/memories';
            $drivePath = $driveFolder . '/' . $filename;
            $finalPath = null;

            try {
                if ($compressed) {
                    Storage::disk('google')->put($drivePath, file_get_contents($destination));
                    @unlink($destination); // Clean up local temporary file
                } else {
                    $file->storeAs($driveFolder, $filename, 'google');
                }
                $finalPath = Storage::disk('google')->url($drivePath);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Google Drive upload failed: " . $e->getMessage() . ". Falling back to local public storage.");
                
                $localFolder = 'memories/' . $folderName;
                if ($compressed && file_exists($destination)) {
                    Storage::disk('public')->put($localFolder . '/' . $filename, file_get_contents($destination));
                    @unlink($destination); // Clean up local temporary file
                } else {
                    $file->storeAs($localFolder, $filename, 'public');
                }
                $finalPath = $localFolder . '/' . $filename;
            }

            // 5. Save to Database
            $memory = Memory::create([
                'trip_id' => $trip->id,
                'user_id' => Auth::id(),
                'file_path' => $finalPath,
                'caption' => $request->caption,
                'taken_at' => $takenAt ?? now(),
                'location' => 'Menganalisis lokasi...', // Temporary loader status
                'ai_tags' => [],
            ]);

            // 6. Dispatch background job for AI tagging & reverse geocoding
            ProcessMemoryImage::dispatch($memory->id, $latitude, $longitude, $drivePath);

            // 7. Log activity
            ActivityLog::log($trip->id, Auth::id(), 'added_memory', Memory::class, $memory->id, [
                'caption' => $memory->caption ?? 'foto baru',
            ]);

            $uploadedCount++;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "$uploadedCount memories uploaded successfully!",
                'count' => $uploadedCount,
            ]);
        }

        return redirect()->route('trips.memories', $trip)->with('success', "$uploadedCount memories uploaded to Google Drive! AI is analyzing details in background.");
    }

    /**
     * Remove the specified memory.
     */
    public function destroy(Trip $trip, Memory $memory)
    {
        // Only uploader or trip organizers can delete memories
        if ($memory->user_id !== Auth::id() && !$trip->isOrganizer(Auth::user())) {
            abort(403, 'Unauthorized action.');
        }

        // Delete from Google Drive if it is a Drive link
        if (str_starts_with($memory->file_path, 'http://') || str_starts_with($memory->file_path, 'https://')) {
            $fileId = null;
            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $memory->file_path, $matches)) {
                $fileId = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $memory->file_path, $matches)) {
                $fileId = $matches[1];
            }

            if ($fileId) {
                try {
                    Storage::disk('google')->delete($fileId);
                } catch (\Throwable $e) {
                    // Ignore errors
                }
            }
        } else {
            // Fallback for legacy local files
            Storage::disk('public')->delete($memory->file_path);
        }

        // Log activity before delete
        ActivityLog::log($trip->id, Auth::id(), 'deleted_memory', Memory::class, $memory->id, [
            'caption' => $memory->caption ?? 'foto memori',
        ]);

        $memory->delete();

        return redirect()->route('trips.memories', $trip)->with('success', 'Memory deleted.');
    }

    /**
     * Toggle the highlighted status of a memory.
     */
    public function toggleHighlight(Trip $trip, Memory $memory)
    {
        // Only uploader or trip organizers can highlight memories
        if ($memory->user_id !== Auth::id() && !$trip->isOrganizer(Auth::user())) {
            abort(403, 'Unauthorized action.');
        }

        $memory->update(['is_highlighted' => !$memory->is_highlighted]);

        $label = $memory->is_highlighted ? 'highlighted' : 'unhighlighted';

        return redirect()->route('trips.memories', $trip)->with('success', "Photo {$label} successfully!");
    }

    /**
     * Serve the image content securely via Laravel's Storage facade.
     */
    public function serveImage(Trip $trip, Memory $memory)
    {
        if ($memory->trip_id !== $trip->id) {
            abort(404);
        }

        $path = $memory->file_path;

        // 1. If it's a full Google Drive URL, try to get the file via Storage disk
        //    The google disk driver can resolve paths from URLs stored in file_path
        try {
            // Try to read via the google disk using the stored path
            // The masbug/flysystem-google-drive-ext driver supports reading by path
            if (str_contains($path, 'drive.google.com') || str_contains($path, 'googleapis.com')) {
                // Extract the file ID from the URL
                $fileId = null;
                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $path, $matches)) {
                    $fileId = $matches[1];
                } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $path, $matches)) {
                    $fileId = $matches[1];
                }

                if ($fileId) {
                    // Use Storage facade with the file ID (google driver supports ID-based access)
                    $stream = Storage::disk('google')->readStream($fileId);
                    $mime = 'image/jpeg';
                    if (str_contains($path, '.png')) {
                        $mime = 'image/png';
                    }
                    return response()->stream(function () use ($stream) {
                        fpassthru($stream);
                    }, 200, [
                        'Content-Type' => $mime,
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("serveImage Drive stream failed: " . $e->getMessage());
        }

        // 2. Fallback to local public storage
        if (Storage::disk('public')->exists($path)) {
            $content = Storage::disk('public')->get($path);
            return response($content, 200, [
                'Content-Type' => Storage::disk('public')->mimeType($path),
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        abort(404);
    }

    /**
     * Convert EXIF GPS coordinate format (rational numbers array) to decimal degree float.
     */
    private static function getGpsCoordinate($gps, $ref)
    {
        $degrees = count($gps) > 0 ? self::gpsRationalToFloat($gps[0]) : 0;
        $minutes = count($gps) > 1 ? self::gpsRationalToFloat($gps[1]) : 0;
        $seconds = count($gps) > 2 ? self::gpsRationalToFloat($gps[2]) : 0;
        
        $flip = ($ref == 'W' || $ref == 'S') ? -1 : 1;
        return $flip * ($degrees + ($minutes / 60) + ($seconds / 3600));
    }

    private static function gpsRationalToFloat($rational)
    {
        $parts = explode('/', $rational);
        if (count($parts) <= 0) return 0;
        if (count($parts) == 1) return (float)$parts[0];
        if ((float)$parts[1] == 0) return 0;
        return (float)$parts[0] / (float)$parts[1];
    }
}
