<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Repositories\CommentRepository;
use App\Services\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly EmailNotificationService $emailService,
    ) {}

    /**
     * List comments for a commentable entity.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'commentable_type' => 'required|string|in:task,project,request',
            'commentable_id' => 'required|integer',
        ]);

        $comments = $this->commentRepository->findByEntity(
            $request->input('commentable_type'),
            $request->input('commentable_id'),
        );

        return response()->json($comments);
    }

    /**
     * Store a new comment. Extracts @mentions from body.
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // Extract @mentions from body
        preg_match_all('/@([\w.]+)/', $data['body'] ?? '', $matches);
        if (!empty($matches[1])) {
            $mentionedIds = DB::table('team_members')
                ->whereIn('name', $matches[1])
                ->orWhere(function ($q) use ($matches) {
                    // Also match by first name or slug-style names
                    foreach ($matches[1] as $mention) {
                        $q->orWhere('name', 'LIKE', $mention . '%');
                    }
                })
                ->pluck('id')
                ->toArray();
            $data['mentions'] = json_encode(array_values(array_unique($mentionedIds)));
        }

        $id = $this->commentRepository->create($data);

        $comment = DB::table('comments')
            ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
            ->select('comments.*', 'team_members.name as user_name')
            ->where('comments.id', $id)
            ->first();

        // Send email notification for comment + mentions
        $mentionedIds = !empty($data['mentions']) ? json_decode($data['mentions'], true) : [];
        try {
            $this->emailService->onCommentAdded(
                $data['commentable_type'],
                $data['commentable_id'],
                auth()->id(),
                $data['body'],
                $mentionedIds,
            );
        } catch (\Throwable $e) {
            // Never let email failure break comment creation
        }

        return response()->json($comment, 201);
    }

    /**
     * Search team members for @mention autocomplete.
     */
    public function mentionSearch(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        $members = DB::table('team_members')
            ->where('is_active', true)
            ->where('name', 'LIKE', '%' . $q . '%')
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($members);
    }
}
