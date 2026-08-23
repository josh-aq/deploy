<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierFeedController extends Controller
{
    public function __construct(private FirebaseService $firebase)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role !== 'supplier') {
                abort(403, 'Unauthorized. Supplier access only.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $allComments = collect($this->firebase->getNewsfeedComments());
        $posts = collect($this->firebase->getNewsfeedPosts())
            ->sortByDesc(fn (array $post) => $post['created_at'] ?? '')
            ->values();
        $likes = $this->firebase->getNewsfeedLikes();

        $userIds = $posts->pluck('user_id')
            ->merge($allComments->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();
        $users = $userIds->isEmpty()
            ? collect()
            : DB::table('users')->whereIn('user_id', $userIds)->get()->keyBy('user_id');

        $posts = $posts->map(function (array $post) use ($allComments, $likes, $users) {
            $post['image_path'] = $post['image_path'] ?? null;
            $post['full_name'] = $this->displayName($users->get($post['user_id']));
            $post['likes_count'] = isset($likes[$post['post_id']]) && is_array($likes[$post['post_id']])
                ? count($likes[$post['post_id']])
                : 0;
            $post['comments_count'] = $allComments->where('post_id', $post['post_id'])->count();

            return (object) $post;
        });

        $comments = $allComments->groupBy('post_id')->map(fn ($postComments) => collect($postComments)->map(function (array $comment) use ($users) {
            $comment['full_name'] = $this->displayName($users->get($comment['user_id']));
            return (object) $comment;
        }));

        $likedPostIds = collect($likes)
            ->filter(fn ($usersForPost) => is_array($usersForPost) && array_key_exists((string) auth()->id(), $usersForPost))
            ->keys()
            ->all();

        return view('supplier.feed', compact('posts', 'comments', 'likedPostIds'));
    }

    private function displayName($user): string
    {
        return $user?->full_name ?: $user?->name ?: $user?->username ?: 'EventIntel member';
    }
}
