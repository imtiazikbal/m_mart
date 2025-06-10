<?php

namespace App\Http\Controllers\api\v1;

use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AboutController extends ResponseController
{
    // storeAbout 
    public function storeAbout(Request $request)
    {
        // Retrieve the first about record (assuming a single-entry table)
        $about = DB::table('abouts_info')->first();
    
        // Check for file uploads and handle them
        $aboutCoverUrl = $aboutImageUrl = $middleImageUrl = $bottomOneImageOneUrl = $bottomOneImageTwoUrl = $bottomTwoImageOneUrl = $bottomTwoImageTwoUrl = null;
    
        if ($request->hasFile('cover')) {
            $aboutCover = $request->file('cover');
            $aboutCoverUrl = S3Service::uploadSingle($aboutCover, 'abouts/covers');
        }
    
        if ($request->hasFile('aboutImage')) {
            $aboutImage = $request->file('aboutImage');
            $aboutImageUrl = S3Service::uploadSingle($aboutImage, 'abouts/about-image');
            if ($about && $about->aboutImage) {
                S3Service::deleteFile($about->aboutImage);
            }
        }
    
        if ($request->hasFile('middleImage')) {
            $middleImage = $request->file('middleImage');
            $middleImageUrl = S3Service::uploadSingle($middleImage, 'abouts/middle-image');
        }
    
        if ($request->hasFile('bottomOneImageOne')) {
            $bottomOneImageOne = $request->file('bottomOneImageOne');
            $bottomOneImageOneUrl = S3Service::uploadSingle($bottomOneImageOne, 'abouts/bottom-one-image-one');
            if ($about && $about->bottomOneImageOne) {
                S3Service::deleteFile($about->bottomOneImageOne);
            }
        }
    
        if ($request->hasFile('bottomOneImageTwo')) {
            $bottomOneImageTwo = $request->file('bottomOneImageTwo');
            $bottomOneImageTwoUrl = S3Service::uploadSingle($bottomOneImageTwo, 'abouts/bottom-one-image-two');
            if ($about && $about->bottomOneImageTwo) {
                S3Service::deleteFile($about->bottomOneImageTwo);
            }
        }
    
        if ($request->hasFile('bottomTwoImageOne')) {
            $bottomTwoImageOne = $request->file('bottomTwoImageOne');
            $bottomTwoImageOneUrl = S3Service::uploadSingle($bottomTwoImageOne, 'abouts/bottom-two-image-one');
            if ($about && $about->bottomTwoImageOne) {
                S3Service::deleteFile($about->bottomTwoImageOne);
            }
        }
    
        if ($request->hasFile('bottomTwoImageTwo')) {
            $bottomTwoImageTwo = $request->file('bottomTwoImageTwo');
            $bottomTwoImageTwoUrl = S3Service::uploadSingle($bottomTwoImageTwo, 'abouts/bottom-two-image-two');
            if ($about && $about->bottomTwoImageTwo) {
                S3Service::deleteFile($about->bottomTwoImageTwo);
            }
        }
    
        // Prepare data for insert or update
        $data = [
            'aboutTitle' => $request->aboutTitle ?? ($about->aboutTitle ?? null),
            'cover' => $aboutCoverUrl ?? ($about->cover ?? null),
            'aboutDescription' => $request->aboutDescription ?? ($about->aboutDescription ?? null),
            'aboutImage' => $aboutImageUrl ?? ($about->aboutImage ?? null),
            'middleTitle' => $request->middleTitle ?? ($about->middleTitle ?? null),
            'middleDescription' => $request->middleDescription ?? ($about->middleDescription ?? null),
            'middleImage' => $middleImageUrl ?? ($about->middleImage ?? null),
            'bottomOneTitle' => $request->bottomOneTitle ?? ($about->bottomOneTitle ?? null),
            'bottomOneDescription' => $request->bottomOneDescription ?? ($about->bottomOneDescription ?? null),
            'bottomOneImageOne' => $bottomOneImageOneUrl ?? ($about->bottomOneImageOne ?? null),
            'bottomOneImageTwo' => $bottomOneImageTwoUrl ?? ($about->bottomOneImageTwo ?? null),
            'bottomTwoTitle' => $request->bottomTwoTitle ?? ($about->bottomTwoTitle ?? null),
            'bottomTwoDescription' => $request->bottomTwoDescription ?? ($about->bottomTwoDescription ?? null),
            'bottomTwoImageOne' => $bottomTwoImageOneUrl ?? ($about->bottomTwoImageOne ?? null),
            'bottomTwoImageTwo' => $bottomTwoImageTwoUrl ?? ($about->bottomTwoImageTwo ?? null),
        ];
    
        if ($about) {
            DB::table('abouts_info')->update($data);
        } else {
            DB::table('abouts_info')->insert($data);
        }
    
        return $this->sendResponse('About created successfully', 'About created successfully.');
    }
    

    // getAbout
    public function getAbout()
    {
        $about = DB::table('abouts_info')->first();
        if($about){
            $modifiedData = [
                'cover' => $about->cover,
                'aboutTitle' => $about->aboutTitle,
                'aboutDescription' => $about->aboutDescription,
                'aboutImage' => $about->aboutImage,
                'middleTitle' => $about->middleTitle,
                'middleDescription' => $about->middleDescription,
                'middleImage' => $about->middleImage,
                'bottomOneTitle' => $about->bottomOneTitle,
                'bottomOneDescription' => $about->bottomOneDescription,
                'bottomOneImageOne' => $about->bottomOneImageOne,
                'bottomOneImageTwo' => $about->bottomOneImageTwo,
                'bottomTwoTitle' => $about->bottomTwoTitle,
                'bottomTwoDescription' => $about->bottomTwoDescription,
                'bottomTwoImageOne' => $about->bottomTwoImageOne,
                'bottomTwoImageTwo' => $about->bottomTwoImageTwo,
            ];
            return $this->sendResponse($modifiedData, 'About retrieved successfully.');
        } else {
            return $this->sendError('About not found', [], 404);
        }
        
    }

}


