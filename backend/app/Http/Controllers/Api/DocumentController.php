<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->isVisibleTo($request->user()), 403, 'You are not assigned to this event.');

        $documents = $event->documents()
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['documents' => $documents]);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        $file = $request->file('file');
        $folder = "documents/event_{$event->id}";
        $path = $file->store($folder, 'local');

        $doc = $event->documents()->create([
            'uploaded_by' => $request->user()->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        $doc->load('uploader:id,name');

        return response()->json(['document' => $doc], 201);
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        abort_unless($document->event->isVisibleTo($request->user()), 403, 'You are not assigned to this event.');
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function destroy(Document $document): JsonResponse
    {
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }
}
