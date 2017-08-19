<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB,View,Session,Validator,Input,Redirect,Hash,Mail,Config,Auth;
use App\User;
use App\Galleries;
use App\GalleryLikes;
use App\Http\Controllers\Api\ApiController;

class GalleryLikesController extends Controller
{
    /**
    *
    *Such num of records will be recieved at loading
    *
    *@var int $take 
    */
    protected $take = 5;

    /**
     * Display a page with likes info.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_arr = Auth::user();
        $allUserPhotosId  =$this->getAllUserPhotos($user_arr->id);
        $data = $this->getLikesDataByUserPhotos($allUserPhotosId,0,$this->take);
        return view('frontend.profile.likes')
            ->with('oldLikes',$data['oldLikesData'])
            ->with('newLikes',$data['newLikesData'])
            ->with('user_arr',$user_arr);
    }

   /**
   * Getting info about like 
   *
   *@param GalleryLikes[] $photosLikes 
   *
   * @return Array
   */
    private function getInfoByPhotoLikeItem($photosLikes)
    {
        $data = [];
        $temp = [];
        foreach ($photosLikes as $item) {
            $temp['user'] = User::find($item['user_id']);
            $temp['user']->profile_pic = ApiController::getPhotoFile('users/thumbnail/'.$temp['user']->profile_pic);
            $temp['photo'] = Galleries::find($item['gallery_id']);
            $temp['photo']->filename = ApiController::getPhotoFile('galleries/thumbnail/'.$temp['photo']->filename);
            $temp['likeData'] = date('M d Y',strtotime($item['created_at']));
            array_push($data,$temp);
            if ($item['readstatus'] == 0) {
                $like = new GalleryLikes();
                $like->setReadstatus($item['id']);
            }
            $temp =[];
        }
        return $data;
    }

    /**
    *Getting all user photos
    *
    *@param int $userId
    *
    *@return Galleries[]
    */
    public function getAllUserPhotos($userId)
    {
        $galleries = new Galleries();
        $allUserPhotosId = $galleries->getAllPhotoIdByUserId($userId);
        return $allUserPhotosId;
    }

    /**
    *Getting GalleryLikes by user's photos
    *
    *@param Array $allUsersPhotosId
    *@param int $skip 
    *@param int $take
    *
    *@return Array with old and new likes
    */
    public function getLikesDataByUserPhotos($allUserPhotosId,$skip=null,$take=null)
    {
        $galleryLikes = new GalleryLikes();
        $photosWithNewLikes = $galleryLikes->getLikesByPhotos($allUserPhotosId,0,$skip,$take);
        $photosWithOldLikes = $galleryLikes->getLikesByPhotos($allUserPhotosId,1,$skip,$take);
        $newLikesData = $this->getInfoByPhotoLikeItem($photosWithNewLikes);
        $oldLikesData = $this->getInfoByPhotoLikeItem($photosWithOldLikes);
        return [
            'oldLikesData' => $oldLikesData,
            'newLikesData' => $newLikesData,
        ];
    }

    /**
    *Load more likes
    *
    *@param  int $readstatus
    *@param int $skip
    *@param int $take
    *
    *@return Array
    */
    public function getMoreLikes($readstatus,$skip,$take)
    {
        $allUserPhotosId  = $this->getAllUserPhotos(Auth::user()->id);
        $galleryLikes = new GalleryLikes();
        $photosWithLikes = $galleryLikes->getLikesByPhotos($allUserPhotosId,$readstatus,$skip,$take);
        $likesData = $this->getInfoByPhotoLikeItem($photosWithLikes);
        return $likesData;
    }

    /**
    *Load more new likes
    *
    *@param int $take
    *
    *@return Array
    */
    public function getMoreNewLikes($skip)
    {
        $take = $this->take;
        $newLikesData = $this->getMoreLikes(0,$skip,$take);
        return $newLikesData;
    }

    /**
    *Load more old likes
    *
    *@param int $take
    *
    *@return Array
    */
    public function getMoreOldLikes($skip)
    {
        $take = $this->take;
        $oldLikesData = $this->getMoreLikes(1,$skip,$take);
        return $oldLikesData;
    }
}
