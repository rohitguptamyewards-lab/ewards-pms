<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Repositories\CommentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
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
     * Store a new comment.
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $id = $this->commentRepository->create($data);

        $comment = DB::table('comments')
            ->leftJoin('team_members', 'comments.user_id', '=', 'team_members.id')
            ->select('comments.*', 'team_members.name as user_name')
            ->where('comments.id', $id)
            ->first();

        return response()->json($comment, 201);
    }
}
