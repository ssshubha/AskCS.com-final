<?php 
namespace App\Http\Controllers;
use App\Posts;
use App\User;
use App\Slugs;
use Redirect;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostFormRequest;
use Illuminate\Http\Request;
class PostController extends Controller
{
    //
    public function index()
  {
    //fetch 5 posts from database which are active and latest
    $posts = Posts::where('active',1)->orderBy('created_at','desc')->paginate(5);
    //page heading
    $title = 'Latest Posts';
    //return home.blade.php template from resources/views folder
    return view('home')->withPosts($posts)->withTitle($title);
  }
  public function create(Request $request)
  {
    // if user can post i.e. user is admin or author
    if($request->user()->can_post())
    {
      return view('posts.create');
    }    
    else 
    {
      return redirect('/home')->withMessage('You do not have sufficient permissions for writing post');
    }
  }
  public function store(PostFormRequest $request)
  {
    $slug=Slugs::find(1);
    $slugnum=$slug->slugcount;
    $forslug=(string)$slugnum;
    $slug->slugcount=$slugnum+1;
    $slug->save();
    $post = new Posts();
    $post->title = $request->get('title');
    $post->body = $request->get('body');
    $post->slug = $forslug;
    $post->author_id = $request->user()->id;
    if($request->has('save'))
    {
      $post->active = 0;
      $message = 'Post saved successfully';            
    }            
    else 
    {
      $post->active = 1;
      $message = 'Post published successfully';
    }
    $post->save();
    //return redirect('edit/'.$post->slug)->withMessage($message);
    return redirect('/home') ->withMessage($message);
  }
  public function show($slug)
  {
    $post = Posts::where('slug',$slug)->first();
    if(!$post)
    {
       return redirect('/home')->withMessage('requested page not found');
    }
    $comments = $post->comments;
    return view('posts.show')->withPost($post)->withComments($comments);
  }
  public function edit(Request $request,$slug)
  {
    $post = Posts::where('slug',$slug)->first();
    if($post && ($request->user()->id == $post->author_id || $request->user()->is_admin()))
      return view('posts.edit')->with('post',$post);
    return redirect('/home')->withMessage('you do not have sufficient permissions');
  }
  public function update(Request $request)
  {
    //
    $post_id = $request->input('post_id');
    $post = Posts::find($post_id);
    if($post && ($post->author_id == $request->user()->id || $request->user()->is_admin()))
    {
      $title = $request->input('title');
      $slug = $post->slug;
      $duplicate = Posts::where('slug',$slug)->first();
      if($duplicate)
      {
        if($duplicate->id != $post_id)
        {
          return redirect('edit/'.$post->slug)->withErrors('Title already exists.')->withInput();
        }
        else 
        {
          $post->slug = $slug;
        }
      }
      $post->title = $request->input('title');
      $post->body = $request->input('body');
      if($request->has('save'))
      {
        $post->active = 0;
        $message = 'Post saved successfully';
        $landing = 'edit/'.$post->slug;
      }            
      else {
        $post->active = 1;
        $message = 'Post updated successfully';
        $landing = $post->slug;
      }
      $post->save();
           return redirect('/home')->withMessage($message);
    }
    else
    {
      return redirect('/home')->withMessage('you do not have sufficient permissions');
    }
  }

  public function destroy(Request $request, $id)
  {
    //
    $post = Posts::find($id);
    if($post && ($post->author_id == $request->user()->id || $request->user()->is_admin()))
    {
      $post->delete();
      $data['message'] = 'Post deleted Successfully';
      return redirect('/home')->with($data);
    }
    else 
    {
      //$data['errors'] = 'Invalid Operation. You have not sufficient permissions';
      return redirect('/home')->withMessage('you do not have sufficient permissions');
    }
    
  }
  public function searchbypostin()
  {
    //
    return view('searchbypostin');
  }
  public function searchbyuserin(){
    return view('searchbyuserin');
  }
  public function searchforpostresult(Request $request){
      $searchstring=$request->input('searchpostname');
      //echo $searchstring;
      $previoussearch=$searchstring;
      $searchstring=$searchstring.'%';
      $posts = Posts::where('active',1)->where('title','LIKE',$searchstring)->orderBy('created_at','desc')->paginate(5);
    //page heading
      $title = 'Posts with Title '.$previoussearch;
    //return home.blade.php template from resources/views folder
      return view('home')->withPosts($posts)->withTitle($title);
  }
  public function searchforuserresult(Request $request){
      $searchstring=$request->input('searchusername');
      //echo $searchstring;
      $previoussearch=$searchstring;
      $searchstring=$searchstring.'%';
      
      $users = User::where('name','LIKE',$searchstring)->paginate(5);
    ////page heading
      $title = 'Users with Name '.$previoussearch;
    ////return home.blade.php template from resources/views folder
      //$users->toArray();
      return view('forusersearchhome')->withUsers($users)->withTitle($title);
  }
}