<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RequestType;
use App\Enums\RequestUrgency;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestFormRequest;
use App\Repositories\CommentRepository;
use App\Repositories\RequestRepository;
use App\Services\RequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RequestController extends Controller
{
    public function __construct(
        private readonly RequestService $requestService,
        private readonly RequestRepository $requestRepository,
        private readonly CommentRepository $commentRepository,
    ) {}

    /**
     * List requests -- Inertia page for web, JSON for API.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $filters = $request->only(['status', 'type', 'urgency', 'search']);
        $requests = $this->requestRepository->findAll($filters, $request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return response()->json($requests);
        }

        return Inertia::render('Requests/Index', [
            'requests' => $requests,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the create form with dropdown data.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Requests/Create', [
            'merchants' => DB::table('merchants')
                ->where('is_active', true)
                ->select('id', 'name')
                ->get(),
            'types' => array_column(RequestType::cases(), 'value'),
            'urgencies' => array_column(RequestUrgency::cases(), 'value'),
        ]);
    }

    /**
     * Store a new request via the repository layer.
     */
    public function store(StoreRequestFormRequest $request)
    {
        $data = $request->validated();
        $data['requested_by'] = auth()->id();

        $id = $this->requestRepository->create($data);
        $pmsRequest = $this->requestRepository->findById($id);

        if ($request->wantsJson()) {
            return response()->json($pmsRequest, 201);
        }

        return redirect()->route('requests.show', $id)
            ->with('success', 'Request created successfully.');
    }

    /**
     * Show a single request with its comments.
     */
    public function show(Request $request, int $id): InertiaResponse|JsonResponse
    {
        $pmsRequest = $this->requestRepository->findById($id);

        if ($request->wantsJson()) {
            return response()->json($pmsRequest);
        }

        $comments = DB::table('comments')
            ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
            ->select('comments.*', 'team_members.name as user_name')
            ->where('comments.commentable_type', 'request')
            ->where('comments.commentable_id', $id)
            ->whereNull('comments.parent_id')
            ->whereNull('comments.deleted_at')
            ->orderByDesc('comments.created_at')
            ->get();

        // Attach replies to each top-level comment
        foreach ($comments as $comment) {
            $comment->replies = DB::table('comments')
                ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
                ->select('comments.*', 'team_members.name as user_name')
                ->where('comments.parent_id', $comment->id)
                ->whereNull('comments.deleted_at')
                ->orderBy('comments.created_at')
                ->get()
                ->toArray();
        }

        $features = DB::table('features')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['released'])
            ->orderBy('title')
            ->select('id', 'title', 'status')
            ->get();

        return Inertia::render('Requests/Show', [
            'request'  => $pmsRequest,
            'comments' => $comments,
            'features' => $features,
        ]);
    }

    /**
     * Update request fields.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->requestRepository->update($id, $request->all());
        $pmsRequest = $this->requestRepository->findById($id);

        return response()->json($pmsRequest);
    }

    /**
     * Check for duplicate requests by title.
     */
    public function checkDuplicates(Request $request): JsonResponse
    {
        $request->validate(['title' => 'required|string']);

        $duplicates = $this->requestService->findDuplicates($request->input('title'));

        return response()->json(['duplicates' => $duplicates]);
    }

    /**
     * Merge a request into a target request.
     */
    public function merge(Request $request, int $id): JsonResponse
    {
        $request->validate(['target_id' => 'required|integer|exists:requests,id']);

        $success = $this->requestService->merge($id, $request->input('target_id'));

        return response()->json([
            'message' => 'Request merged successfully.',
            'success' => $success,
        ]);
    }

    /**
     * Triage a request (accept, defer, reject, etc.).
     */
    public function triage(Request $request, int $id)
    {
        $request->validate([
            'action' => 'required|string|in:accept,defer,reject,clarify',
            'reason' => 'nullable|string',
        ]);

        $this->requestService->triage(
            $id,
            $request->input('action'),
            $request->input('reason'),
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Request triaged successfully.',
                'request' => $this->requestRepository->findById($id),
            ]);
        }

        return redirect()->route('requests.show', $id)
            ->with('success', 'Request triaged successfully.');
    }
}
