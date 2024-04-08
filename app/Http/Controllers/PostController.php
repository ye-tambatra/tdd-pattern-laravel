<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 12);
        $posts = Post::paginate($perPage);
        return response()->json($posts);
    }

    public function show($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return $this->notFound($id);
        }
        return response()->json($post);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string'
        ]);

        $user = $request->user();
        $post = new Post;
        $post->title = $data['title'];
        $post->content = $data['content'];
        $user->posts()->save($post);

        return response()->json($post, 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $post = Post::find($id);

        if (!$post) {
            return $this->notFound($id);
        }

        if (!$user->isAdmin() && $post->user_id != $user->id) {
            return $this->unauthtorized();
        }

        $post->delete();
        return response()->json($post);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'title' => 'sometimes|string',
            'content' => 'sometimes|string'
        ]);

        $user = $request->user();

        $post = Post::find($id);

        if (!$post) {
            return $this->notFound($id);
        }

        if ($user->id != $post->user_id) {
            return $this->unauthtorized();
        }

        $post->update($request->only(['title', 'content']));
        return response()->json($post);
    }

    private function notFound($id)
    {
        return response()->json([
            'message' => 'Post not found with the id ' . $id
        ], 404);
    }

    private function unauthtorized()
    {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }
}
