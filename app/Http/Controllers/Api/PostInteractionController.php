<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Http\Request;

class PostInteractionController extends Controller
{
    // ──────────────────── LIKES ────────────────────

    public function toggleLike(Request $request, Post $post)
    {
        $user = $request->user();
        $existing = PostLike::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            PostLike::create(['post_id' => $post->id, 'user_id' => $user->id]);
            $liked = true;
        }

        return response()->json([
            'liked'       => $liked,
            'likes_count' => $post->likes()->count(),
        ]);
    }

    // ──────────────────── COMMENTS ────────────────────

    public function comments(Post $post)
    {
        $userId = request()->user()->id;

        $all = PostComment::where('post_id', $post->id)
            ->with('user')
            ->withCount('likes')
            ->oldest()
            ->get();

        // Append user_liked to each comment
        $all->each(function ($c) use ($userId) {
            $c->user_liked = $c->likes()->where('user_id', $userId)->exists();
        });

        return response()->json(['data' => $all]);
    }

    public function storeComment(Request $request, Post $post)
    {
        $validated = $request->validate([
            'body'      => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:post_comments,id',
        ]);

        // Ensure parent belongs to the same post
        if (!empty($validated['parent_id'])) {
            $parent = PostComment::findOrFail($validated['parent_id']);
            if ($parent->post_id !== $post->id) {
                return response()->json(['message' => 'Comentario padre no pertenece a este post'], 422);
            }
        }

        $comment = PostComment::create([
            'post_id'   => $post->id,
            'user_id'   => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body'      => $validated['body'],
        ]);

        $comment->load('user');

        return response()->json(['success' => true, 'data' => $comment], 201);
    }

    public function destroyComment(Request $request, Post $post, PostComment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $comment->delete();
        return response()->json(['success' => true]);
    }

    public function toggleCommentLike(Request $request, PostComment $comment)
    {
        $user = $request->user();
        $existing = \App\Models\CommentLike::where('post_comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            \App\Models\CommentLike::create(['post_comment_id' => $comment->id, 'user_id' => $user->id]);
            $liked = true;
        }

        return response()->json([
            'liked'       => $liked,
            'likes_count' => $comment->likes()->count(),
        ]);
    }
}
