<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Models\Liveinfo;
use App\Facades\Leancloud;
use App\Models\Cover;
use App\Models\Danmaku;

class ShowController extends Controller
{
    public function __construct()
    {
        view()->share('title','Niconiconi');
    }

    public function index(){
        $livingInfo = [];
        $livingUser = Liveinfo::select('uid','title','description')->where('ctime','>',time()-43200)->orderBy('id', 'desc')->get();
        foreach ($livingUser as $user){
            $arr['uid'] = $user['uid'];
            $arr['title'] = $user['title'];
            $arr['description'] = $user['description'];
            $userCover = Cover::select('cover')->where('uid',$user['uid'])->first();
            $arr['cover'] = $userCover['cover'];
            array_push($livingInfo,$arr);
        }

        $livecount = count($livingInfo);

        return view('index',[
            'count' => $livecount,
            'liveInfo' => $livingInfo,
        ]);
    }

    public function show($id){
        $showinfo = DB::table('liveinfo')->select('title','description','activityId')->where('uid',$id)->first();
        $userinfo = DB::table('users')->select('name','email')->where('id',$id)->first();
        $danmakuinfo = DB::table('danmaku')->select('roomId')->where('uid',$id)->first();
        if(!$userinfo||!$showinfo){
            return redirect()->route('index');
        }
        if(!$danmakuinfo){
            $json = Leancloud::createRoom('room'.$id);
            $res_arr = json_decode($json,true);
            $roomId = array_key_exists('objectId', $res_arr) ? $res_arr['objectId'] : false;
            if($roomId){
                $danmaku = new Danmaku();
                $danmaku->uid = $id;
                $danmaku->roomId = $roomId;
                $danmaku->save();
            }
        }else{
            $roomId = $danmakuinfo->roomId;
        }

        $title = $showinfo->title;
        $description = $showinfo->description;
        $name = $userinfo->name;
        $activityId = $showinfo->activityId;
        $email = $userinfo->email;
        $appId = Leancloud::getAppId();


        return view('show',[
            'title' => $title,
            'description' => $description,
            'name' => $name,
            'activityId' => $activityId,
            'email' => $email,
            'appId' => $appId,
            'roomId' => $roomId,
        ]);
    }
}
