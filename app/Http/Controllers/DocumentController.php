<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Document;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Display the document vault for the trip.
     */
    public function index(Trip $trip)
    {
        $documents = $trip->documents()->with('user')->latest()->get();

        $documents = $documents->map(function ($doc) {
            $isGoogleDrive = str_starts_with($doc->file_path, 'http://') || str_starts_with($doc->file_path, 'https://');

            // Generate direct url for quick view
            if ($isGoogleDrive) {
                $doc->url = $doc->file_path;

                // Convert Google Drive view URL to preview URL for iframe embedding
                if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $doc->file_path, $matches)) {
                    $doc->preview_url = "https://drive.google.com/file/d/{$matches[1]}/preview";
                } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $doc->file_path, $matches)) {
                    $doc->preview_url = "https://drive.google.com/file/d/{$matches[1]}/preview";
                } else {
                    $doc->preview_url = $doc->file_path;
                }
            } else {
                $doc->url = asset('storage/' . $doc->file_path);
                $doc->preview_url = asset('storage/' . $doc->file_path);
            }

            return $doc;
        });

        return view('trips.documents', compact('trip', 'documents'));
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(Request $request, Trip $trip)
    {
        $request->validate([
            'document' => ['required_without:drive_url', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'drive_url' => ['required_without:document', 'url', 'nullable'],
            'drive_title' => ['required_with:drive_url', 'string', 'max:255', 'nullable'],
            'category' => ['required', 'string', 'in:transport,accommodation,logistics,general,photos_media'],
        ]);

        if ($request->filled('drive_url')) {
            $document = Document::create([
                'trip_id' => $trip->id,
                'user_id' => Auth::id(),
                'title' => $request->drive_title,
                'file_path' => $request->drive_url,
                'file_type' => 'drive',
                'file_size' => 0,
                'category' => $request->category,
            ]);
        } else {
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();

            // Determine file type category (image, pdf, document)
            $fileType = match (strtolower($extension)) {
                'pdf' => 'pdf',
                'jpg', 'jpeg', 'png' => 'image',
                'doc', 'docx' => 'word',
                default => 'other'
            };

            // Ensure unique filename
            $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
            
            // Create a clean, readable folder name based on the Trip title
            $folderName = Str::slug($trip->title) . '_' . $trip->id;
            
            // Store directly on the Google Drive disk!
            $path = $file->storeAs($folderName . '/documents', $filename, 'google');
            
            // Get the public/webView URL of the uploaded file on Google Drive
            try {
                $driveUrl = Storage::disk('google')->url($path);
            } catch (\Throwable $e) {
                // Fallback if URL resolution fails
                $driveUrl = $path;
            }

            $document = Document::create([
                'trip_id' => $trip->id,
                'user_id' => Auth::id(),
                'title' => $originalName,
                'file_path' => $driveUrl,
                'file_type' => $fileType,
                'file_size' => $size,
                'category' => $request->category,
            ]);
        }

        ActivityLog::log($trip->id, Auth::id(), 'uploaded_document', Document::class, $document->id, [
            'title' => $document->title,
        ]);

        return redirect()->route('trips.documents', $trip)->with('success', 'Document added successfully.');
    }

    /**
     * Download a document.
     */
    public function download(Trip $trip, Document $document)
    {
        if ($document->file_type === 'drive' || str_starts_with($document->file_path, 'http://') || str_starts_with($document->file_path, 'https://')) {
            return redirect()->away($document->file_path);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->title);
    }

    /**
     * Remove the specified document.
     */
    public function destroy(Trip $trip, Document $document)
    {
        if ($document->user_id !== Auth::id() && !$trip->organizers->contains(Auth::user())) {
            abort(403, 'Unauthorized action.');
        }

        if (str_starts_with($document->file_path, 'https://drive.google.com') && $document->file_type !== 'drive') {
            // Extract the File ID and delete it from Google Drive
            $fileId = null;
            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $document->file_path, $matches)) {
                $fileId = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $document->file_path, $matches)) {
                $fileId = $matches[1];
            }

            if ($fileId) {
                try {
                    Storage::disk('google')->delete($fileId);
                } catch (\Throwable $e) {
                    // Ignore errors if the file was manually deleted from Drive
                }
            }
        } elseif ($document->file_type !== 'drive' && !str_starts_with($document->file_path, 'http')) {
            // Legacy local file cleanup
            Storage::disk('public')->delete($document->file_path);
        }
        
        $title = $document->title;
        $document->delete();

        ActivityLog::log($trip->id, Auth::id(), 'deleted_document', Document::class, $document->id, [
            'title' => $title,
        ]);

        return redirect()->route('trips.documents', $trip)->with('success', 'Document deleted.');
    }
}
