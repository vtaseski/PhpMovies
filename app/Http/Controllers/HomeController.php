<?php

namespace App\Http\Controllers;

use App\DBImpl;
use App\Movie;
use App\TMDBImpl;
use App\TMDb;
use App\User;
use Validator;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
       // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $movies = TMDBImpl::getMovies();
       //usort($movies, "SortImpl::cmp_popularity");
       //$movies[0]
       return view('home', ['movies' => $movies]);
        //return view('home')->with('movies', 'balu');//->withMovies($movies);
    }
    public function indexSorted(){
        $genre = $_POST["order"];
        $movies = TMDBImpl::getMovies();
        usort($movies, "SortImpl::" . $genre);
        $movies = array_reverse($movies);
        return view('home', ['movies' => $movies]);
    }

    public function show($id)
    {
        $singlemovie = TMDBImpl::getMovie($id);
        //return $singlemovie;
        $comments=DBImpl::getCommentsForMovie($id);
        $data['id']=$id;
        $user= Auth::user()['id'];
        $watched=DBImpl::getMoviesWatchedByUser($user);
        $iswatched = false;

        foreach ($watched as $movie) {
            if($movie == $id){
                $iswatched = true;
            }
        }
        //return var_dump($iswatched);
        return view('singlemovie',['singlemovie'=> $singlemovie], ['comments'=>$comments])->with('iswatched', $iswatched );
    }
    public function search()
    {
        $singlemovie = TMDBImpl::getMovie($_GET["q"]);
        $comments=DBImpl::getCommentsForMovie($singlemovie[0]->id);
        $user= Auth::user()['id'];
        $watched=DBImpl::getMoviesWatchedByUser($user);
        $iswatched = false;
        foreach ($watched as $movie) {
            if($movie == $singlemovie[0]->id){
                $iswatched = true;
            }
        }
        //return var_dump($iswatched);
        return view('singlemovie',['singlemovie'=> $singlemovie], ['comments'=>$comments])->with('iswatched', $iswatched );
    }

    public function storeComment(Request $request,$id)

    {
        $data['id']=$id;
        $user= Auth::user()['id'];
        $comment=$request->comment;

        DBImpl::insertIntoUserMoviesComments($user,$id,$comment);
        return back();
    }
    public function profile(Request $request, $id) {
        $data['user'] = User::find($id);
        $img = DBImpl::getPictureByUser($id);
        //return $img;
        return view('profile', $data);
    }
    public function storeWatched($id) {
        $data['id']=$id;
        $user= Auth::user()['id'];
        //return $user;
        DBImpl::insertIntoUserMovies($user, $id);
        return back();
    }
    public function storeRating($id){
        $rate = $_POST["rating"];
        $data['id']=$id;
        $user= Auth::user()['id'];
        //return $user;
        DBImpl::insertIntoRatings($user, $id, $rate);
        return back();
    }

    public function usermovies(){
        $user= Auth::user()['id'];
        $test = new TMDBImpl();
        $watched=DBImpl::getMoviesWatchedByUser($user);
        // $watched se id-ata, a $movie ti e sekoj id poedinecno
        $movies = [];

        foreach ($watched as $movie) {
            $m = $test->getMovie($movie);
            array_push($movies, $m);
        }
        $suggest1=[];
        $suggest2=[];

        if(count($watched)>1)
        {
            $suggest1=TMDBImpl::getSimilarMovies($watched[0]);
            $suggest1=array_slice($suggest1,0,2);
            $suggest2=TMDBImpl::getSimilarMovies($watched[1]);
            $suggest2=array_slice($suggest2,0,2);

        }
        else if(count($watched)!=0)
        {
            $suggest1=TMDBImpl::getSimilarMovies($watched[0]);
            $suggest1=array_slice($suggest1,0,4);
        }

        return view('usermovies',['movies'=> $movies],['suggestions1'=>$suggest1])->with('suggestions2',$suggest2);
    }

    public function storePicture(Request $request) {

        $file = $request->file('userfile');
        //return $file;
        $destination_path = 'uploads/';
        $filename = str_random(6).'_'.$file->getClientOriginalName();
        //return $filename;
        $file->move($destination_path, $filename);
        $des = $destination_path . $filename;
        DBImpl::insertIntoUserPicture($request->user()->id, $des);
        return back();

    }

}
