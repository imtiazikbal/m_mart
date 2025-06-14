<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class VideoBannerController extends ResponseController
{
    // store and update top category
   public function store(Request $request)
{
    try {
        // Upload the new image to S3
        $file = $request->file('videoThumbnail');
        $path = 'video_banner';
        $videoThumbnailUrl = S3Service::uploadSingle($file, $path);

        // Check if a category banner already exists
        $categoryBanner = DB::table('video_banner')->first();

        if ($categoryBanner) {
            // Delete the old image from S3 if it exists
            if ($categoryBanner->image) {
                S3Service::deleteFile($categoryBanner->image);
            }

            // Update the existing category banner
            DB::table('video_banner')->where('id', $categoryBanner->id)->update([
                'videoUrl' => $request->videoUrl,
                'videoThumbnail' => $videoThumbnailUrl,
                'categoryId' => $request->categoryId,
                'updated_at' => now(),
            ]);
        } else {
            // Insert a new category banner
            DB::table('video_banner')->insert([
                'videoUrl' => $request->videoUrl,
                'videoThumbnail' => $videoThumbnailUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->sendResponse('Video Banner created successfully', 'Video Banner created successfully');
    } catch (Exception $e) {
        // Optionally log the error: Log::error($e->getMessage());
        return $this->sendError('Something went wrong', [], 500);
    }
}


    // get all top categories
    public function getAll(){
        try{
            $topCategories = DB::table('video_banner')->
            select('video_banner.videoUrl','video_banner.videoThumbnail')->
            first();
            return $this->sendResponse($topCategories, 'Video Banner fetched successfully');
           }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
           }
    }

    // getAllForFrontend
    public function getAllForFrontend(){
        try{
            $topCategories = DB::table('video_banner')->
            select('video_banner.videoUrl','video_banner.videoThumbnail')->
            first();
            return $this->sendResponse($topCategories, 'Video Banner fetched successfully');
           }catch(Exception $e){
            return $this->sendError('Something went wrong', [], 500);
           }
    }
}
