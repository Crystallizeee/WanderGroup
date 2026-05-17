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
        return view('trips.documents', compact('trip', 'documents'));
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(Request $request, Trip $trip)
    {
        $request->validate([
            'document' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'category' => ['required', 'string', 'in:transport,accommodation,logistics,general'],
        ]);

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
        $path = $file->storeAs('trips/' . $trip->id . '/documents', $filename, 'public');

        $document = Document::create([
            'trip_id' => $trip->id,
            'user_id' => Auth::id(),
            'title' => $originalName,
            'file_path' => $path,
            'file_type' => $fileType,
            'file_size' => $size,
            'category' => $request->category,
        ]);

        ActivityLog::log($trip->id, Auth::id(), 'uploaded_document', Document::class, $document->id, [
            'title' => $document->title,
        ]);

        return redirect()->route('trips.documents', $trip)->with('success', 'Document uploaded successfully.');
    }

    /**
     * Download a document.
     */
    public function download(Trip $trip, Document $document)
    {
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

        Storage::disk('public')->delete($document->file_path);
        
        $title = $document->title;
        $document->delete();

        ActivityLog::log($trip->id, Auth::id(), 'deleted_document', Document::class, $document->id, [
            'title' => $title,
        ]);

        return redirect()->route('trips.documents', $trip)->with('success', 'Document deleted.');
    }
}
