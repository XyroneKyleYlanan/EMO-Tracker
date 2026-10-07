<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        $documents = $event->documents()
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['documents' => $documents]);
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        // PHP turns away a file over its own limit before the app sees it.
        $upload = $request->file('file');
        $overLimit = $upload instanceof UploadedFile
            && in_array($upload->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:'.Document::MAX_UPLOAD_KB,
            ],
        ], [
            'file.uploaded' => $overLimit ? Document::tooLargeMessage() : "The file didn't upload completely. Please try again.",
            'file.max' => Document::tooLargeMessage(),
            'file.mimes' => "This kind of file isn't accepted. Use a PDF, Word, Excel, JPG or PNG file.",
        ]);

        $file = $request->file('file');
        $path = $file->store($event->documentsFolder(), 'local');

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

    public function download(Document $document): StreamedResponse
    {
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
