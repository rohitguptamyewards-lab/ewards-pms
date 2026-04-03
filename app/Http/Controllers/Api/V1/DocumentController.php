<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\DocumentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {}

    /**
     * Upload and store a document.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10 MB max
            'documentable_type' => 'required|string|in:task,project,request,feature',
            'documentable_id' => 'required|integer',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'local');

        $id = $this->documentRepository->create([
            'documentable_type' => $request->input('documentable_type'),
            'documentable_id' => $request->input('documentable_id'),
            'uploaded_by' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        $document = $this->documentRepository->findById($id);

        return response()->json($document, 201);
    }

    /**
     * Download a document.
     */
    public function show(int $id): StreamedResponse
    {
        $document = $this->documentRepository->findById($id);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'File not found.');

        return Storage::disk('local')->download(
            $document->file_path,
            $document->file_name,
        );
    }

    /**
     * Delete a document and its file from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $document = $this->documentRepository->findById($id);

        Storage::disk('local')->delete($document->file_path);
        $this->documentRepository->delete($id);

        return response()->json(['message' => 'Document deleted successfully.']);
    }
}
