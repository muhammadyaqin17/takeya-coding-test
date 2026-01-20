<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     * Retrieve a paginated list of active posts (20 per page)
     * Include the author (user) data for each post
     * Exclude draft and scheduled posts
     */
    public function index(): JsonResponse
    {
        $posts = Post::with('user')
            ->published()
            ->latest('published_at')
            ->paginate(20);

        return response()->json($posts);
    }

    /**
     * Show the form for creating a new resource.
     * Only authenticated users can access this route
     */
    public function create(): string
    {
        return 'posts.create';
    }

    /**
     * Store a newly created resource in storage.
     * Only authenticated users can create new posts
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $request->user()->posts()->create($request->validated());

        return response()->json($post, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     * Retrieve a single active post
     * Return 404 if the post is a draft or scheduled
     */
    public function show(Post $post): JsonResponse
    {
        // Return 404 if post is not published (is draft or scheduled)
        if (! $post->isPublished()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return response()->json($post);
    }

    /**
     * Show the form for editing the specified resource.
     * Only the post author can access this route
     */
    public function edit(Post $post): string
    {
        $this->authorize('update', $post);

        return 'posts.edit';
    }

    /**
     * Update the specified resource in storage.
     * Only the post author can update the post
     */
    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        // Authorization is handled by UpdatePostRequest::authorize()
        $post->update($request->validated());

        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     * Only the post author can delete the post
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(['deleted' => true]);
    }
}
