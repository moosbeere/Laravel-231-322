<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Jobs\VeryLongJob;
use App\Notifications\NewCommentNotify;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{

    public function index(){
        $page = isset($_GET['page']) ? $_GET['page'] : 0;
        $comments = Cache::remember('comments'.$page, 3000, function(){
            return Comment::latest()->paginate(10);
        });
        return view('comment.index', ['comments'=>$comments]);
    }

    public function store(Request $request){
        $keys = DB::table('cache')->whereRaw('`key` GLOB :key', [':key'=>'comments*[0-9]'])->get();
        foreach($keys as $param){
            Cache::forget($param->key);
        }
        $request->validate([
            'name'=>'required|min:4',
            'desc'=>'required|max:256'
        ]);

        $comment = new Comment;
        $comment->name = request('name');
        $comment->desc = request('desc'); 
        $comment->article_id = request('article_id');
        $comment->user_id = Auth::id();
        if ($comment->save()){
            VeryLongJob::dispatch($comment);
            return redirect()->back()->with('status', 'New comment send to moderation');
        }
        else return redirect()->back()->with('status', 'Add failed');        
    }

    public function edit($id){
        $comment = Comment::findOrFail($id);
        Gate::authorize('update_comment', $comment);
        return view('comment.update', ['comment'=>$comment]);
    }

    public function update(Request $request, Comment $comment){
        $keys = DB::table('cache')->whereRaw('`key` GLOB :key', [':key'=>'comments*[0-9]'])->get();
        foreach($keys as $param){
            Cache::forget($param->key);
        }
        $keys = DB::table('cache')->whereRaw('`key` GLOB :key', [':key'=>'comment_article'.$comment->article_id])->get();
        foreach($keys as $param){
            Cache::forget($param->key);
        }
        Gate::authorize('update_comment', $comment);
        $request->validate([
            'name'=>'required|min:4',
            'desc'=>'required|max:256'
        ]);

        $comment->name = request('name');
        $comment->desc = request('desc'); 
        if ($comment->save()) return redirect()->route('article.show', $comment->article_id)->with('status', 'Comment update success');
        else return redirect()->back()->with('status', 'Update failed'); 
    }

    public function destroy(Comment $comment){
        Cache::flush();
        Gate::authorize('update_comment', $comment);
        if ($comment->delete()) return redirect()->route('article.show', $comment->article_id)->with('status', 'Delete comment success');
        else return redirect()->route('article.show', $comment->article_id)->with('status', 'Delete comment failed');

    }

    public function accept(Comment $comment){
        $keys = DB::table('cache')->whereRaw('`key` GLOB :key', [':key'=>'comments*[0-9]'])->get();
        foreach($keys as $param){
            Cache::forget($param->key);
        }
        $keys = DB::table('cache')->whereRaw('`key` GLOB :key', [':key'=>'comment_article'.$comment->article_id])->get();
        foreach($keys as $param){
            Cache::forget($param->key);
        }
        $article = Article::findOrFail($comment->article_id);
        $users = User::where('id', '!=', $comment->user_id)->get();
        $comment->accept = true;
        if ($comment->save()) Notification::send($users, new NewCommentNotify($article, $comment->name));
        return redirect()->route('comment.index');
    }

    public function reject(Comment $comment){
        Cache::flush();
        $comment->accept = false;
        $comment->save();
        return redirect()->route('comment.index');
    }
}