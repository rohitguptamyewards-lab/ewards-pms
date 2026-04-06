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
     * Upload a file or save an external link as a document.
     *
     * For file uploads: send multipart form with 'file' field.
     * For links: send JSON with 'type' = 'link', 'link_url', and 'file_name'.
     */
    public function store(Request $request): JsonResponse
    {
        $type = $request->input('type', 'file');

        $request->validate([
            'type'             => ['nullable', 'in:file,link'],
            'documentable_type' => ['required', 'string', 'in:task,project,request,feature,initiative,idea,decision'],
            'documentable_id'  => ['required', 'integer'],
            'description'      => ['nullable', 'string', 'max:500'],
        ]);

        if ($type === 'link') {
            $request->validate([
                'link_url'  => ['required', 'url', 'max:2000'],
                'file_name' => ['required', 'string', 'max:255'],
            ]);

            $id = $this->documentRepository->create([
                'documentable_type' => $request->input('documentable_type'),
                'documentable_id'   => $request->input('documentable_id'),
                'uploaded_by'       => auth()->id(),
                'file_name'         => $request->input('file_name'),
                'file_path'         => null,
                'file_size'         => null,
                'mime_type'         => null,
                'type'              => 'link',
                'link_url'          => $request->input('link_url'),
                'description'       => $request->input('description'),
            ]);
        } else {
            $request->validate([
                'file' => ['required', 'file', 'max:10240'],
            ]);

            $file = $request->file('file');
            $path = $file->store('documents', 'local');

            $id = $this->documentRepository->create([
                'documentable_type' => $request->input('documentable_type'),
                'documentable_id'   => $request->input('documentable_id'),
                'uploaded_by'       => auth()->id(),
                'file_name'         => $file->getClientOriginalName(),
                'file_path'         => $path,
                'file_size'         => $file->getSize(),
                'mime_type'         => $file->getMimeType(),
                'type'              => 'file',
                'link_url'          => null,
                'description'       => $request->input('description'),
            ]);
        }

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

    private function isManager(): bool
    {
        $role = auth()->user()->role;
        $value = $role instanceof \App\Enums\Role ? $role->value : (string) $role;
        return in_array($value, ['cto', 'ceo', 'manager']);
    }

    /**
     * Delete a document and its file from storage (skipped for link-type).
     * Only the uploader or a manager can delete documents.
     */
    public function destroy(int $id): JsonResponse
    {
        $document = $this->documentRepository->findById($id);

        abort_unless(
            $document->uploaded_by === auth()->id() || $this->isManager(),
            403,
            'You can only delete your own documents.'
        );

        if (($document->type ?? 'file') === 'file' && $document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $this->documentRepository->delete($id);

        return response()->json(['message' => 'Document deleted successfully.']);
    }
}
