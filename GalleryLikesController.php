<?php

namespace App\Http\Controllers\Frontend;

use App\Galleries;
use App\GalleryLikes;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\User;
use DB,View,Session,Validator,Input,Redirect,Hash,Mail,Config,Auth;
use Illuminate\Http\Request;

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
        $userArr = Auth::user();
        $allUserPhotosId  = $this->getAllUserPhotos($userArr->id);
        $data = $this->getLikesDataByUserPhotos($allUserPhotosId, 0, $this->take);
        return view('frontend.profile.likes')
            ->with('oldLikes', $data['oldLikesData'])
            ->with('newLikes', $data['newLikesData'])
            ->with('user_arr', $userArr);
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
        return $this->getMoreLikes(0, $skip, $this->take);
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
        return $this->getMoreLikes(1, $skip, $this->take);
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
            $temp['likeData'] = date('M d Y', strtotime($item['created_at']));
            array_push($data, $temp);
            if ($item['readstatus'] == 0) {
                $like = GalkeryLikes::find($item['id']);
                $like-setReadstatus(1);
                $like->save();
            }
            $temp = [];
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
    private function getAllUserPhotos($userId)
    {
        return (new Galleries())->getAllPhotoIdByUserId($userId);
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
    private function getLikesDataByUserPhotos($allUserPhotosId,$skip = null,$take = null)
    {
        $photosWithNewLikes = (new GalleryLikes())->getLikesByPhotos($allUserPhotosId, 0, $skip, $take);
        $photosWithOldLikes = (new GalleryLikes())->getLikesByPhotos($allUserPhotosId, 1, $skip, $take);
        return [
            'oldLikesData' => getInfoByPhotoLikeItem($photosWithOldLikes),
            'newLikesData' => $this->getInfoByPhotoLikeItem($photosWithNewLikes),
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
    private function getMoreLikes($readstatus,$skip,$take)
    {
        $allUserPhotosId  = $this->getAllUserPhotos(Auth::user()->id);
        $photosWithLikes = (new GalleryLikes())->getLikesByPhotos($allUserPhotosId, $readstatus, $skip, $take);
        $likesData = $this->getInfoByPhotoLikeItem($photosWithLikes);
        return $likesData;
    }

    
}
