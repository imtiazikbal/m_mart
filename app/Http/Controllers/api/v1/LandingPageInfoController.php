<?php

namespace App\Http\Controllers\api\v1;

use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class LandingPageInfoController extends ResponseController
{
    // storeLandingPageInfo
    public function storeLandingPageInfo(Request $request)
{
    $landingPageInfo = DB::table('landing_pages_info')->first();

    // Check for file uploads
    if ($request->hasFile('topBanner') || $request->hasFile('bottomBanner') || $request->hasFile('logo') || $request->hasFile('favicon')) {
        if ($request->hasFile('favicon')) {
            $faviconFile = $request->file('favicon');
            $faviconUrl = S3Service::uploadSingle($faviconFile, 'landing_pages_favicon');

            // Only delete old image if $landingPageInfo is not null and has a favicon
            if ($landingPageInfo && $landingPageInfo->favicon) {
                S3Service::deleteFile($landingPageInfo->favicon);
            }
        }

        if ($request->hasFile('topBanner')) {
            $topBannerFile = $request->file('topBanner');
            $topBannerUrl = S3Service::uploadSingle($topBannerFile, 'landing_pages_top_banner');

            if ($landingPageInfo && $landingPageInfo->topBanner) {
                S3Service::deleteFile($landingPageInfo->topBanner);
            }
        }

        if ($request->hasFile('bottomBanner')) {
            $bottomBannerFile = $request->file('bottomBanner');
            $bottomBannerUrl = S3Service::uploadSingle($bottomBannerFile, 'landing_pages_bottom_banner');

            if ($landingPageInfo && $landingPageInfo->bottomBanner) {
                S3Service::deleteFile($landingPageInfo->bottomBanner);
            }
        }

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoUrl = S3Service::uploadSingle($logoFile, 'landing_pages_logo');

            if ($landingPageInfo && $landingPageInfo->logo) {
                S3Service::deleteFile($landingPageInfo->logo);
            }
        }
    }

    // Check if landing page info exists and update or insert accordingly
    if ($landingPageInfo) {
        DB::table('landing_pages_info')->update([
            'favicon' => $faviconUrl ?? $landingPageInfo->favicon,
            'notice' => $request->notice ?? $landingPageInfo->notice,
            'topBanner' => $topBannerUrl ?? $landingPageInfo->topBanner,
            'bottomBanner' => $bottomBannerUrl ?? $landingPageInfo->bottomBanner,
            'logo' => $logoUrl ?? $landingPageInfo->logo,
            'facebook' => $request->facebook ?? $landingPageInfo->facebook,
            'linkedin' => $request->linkedin ?? $landingPageInfo->linkedin,
            'instagram' => $request->instagram ?? $landingPageInfo->instagram,
            'whatsapp' => $request->whatsapp ?? $landingPageInfo->whatsapp,
            'pinterest' => $request->pinterest ?? $landingPageInfo->pinterest,
            'copyrightText' => $request->copyrightText ?? $landingPageInfo->copyrightText,
            'footerCategories' => $request->footerCategories ?? $landingPageInfo->footerCategories,
            'companyName' => $request->companyName ?? $landingPageInfo->companyName,
            'name' => $request->name ?? $landingPageInfo->name,
            'storeName' => $request->storeName ?? $landingPageInfo->storeName,
            'address' => $request->address ?? $landingPageInfo->address,
            'email' => $request->email ?? $landingPageInfo->email,
            'phone' => $request->phone ?? $landingPageInfo->phone,
        ]);
    } else {
        DB::table('landing_pages_info')->insert([
            'favicon' => $faviconUrl ?? null,
            'notice' => $request->notice,
            'topBanner' => $topBannerUrl ?? null,
            'bottomBanner' => $bottomBannerUrl ?? null,
            'logo' => $logoUrl ?? null,
            'facebook' => $request->facebook,
            'linkedin' => $request->linkedin,
            'instagram' => $request->instagram,
            'whatsapp' => $request->whatsapp,
            'pinterest' => $request->pinterest,
            'copyrightText' => $request->copyrightText,
            'footerCategories' => $request->footerCategories,
            'companyName' => $request->companyName ?? null,
            'name' => $request->name ,
            'storeName' => $request->storeName ?? null,
            'address' => $request->address ,
            'email' => $request->email,
            'phone' => $request->phone,
           
        ]);
    }

    return $this->sendResponse('Landing Page Info updated successfully', 'Landing Page Info updated successfully.');
}



    // getLandingPageInfo 
//     public function getLandingPageInfo()
//     {
     
//         $landingPageInfo = DB::table('landing_pages_info')
//         ->select(
//             'landing_pages_info.notice',
//             'landing_pages_info.topBanner',
//             'landing_pages_info.bottomBanner',
//             'landing_pages_info.logo',
//             'landing_pages_info.facebook',
//             'landing_pages_info.linkedin',
//             'landing_pages_info.instagram',
//             'landing_pages_info.whatsapp',
//             'landing_pages_info.telegram',
//             'landing_pages_info.copyrightText',
//             'landing_pages_info.footerCategories'
//         )
//         ->get();
    
//    // Process and join category names
// $landingPageInfo = $landingPageInfo->map(function ($item) {
//     // Decode footerCategories from JSON string to an array
//     $footerCategoryIds = json_decode($item->footerCategories, true);

//     // Check if the decoded value is an array
//     if (is_array($footerCategoryIds) && !empty($footerCategoryIds)) {
//         // Retrieve category names based on decoded IDs
//         $categoryNames = DB::table('categories')
//             ->whereIn('id', $footerCategoryIds)
//             ->pluck('name')
//             ->toArray();

//         // Set footerCategories as the array of category names
//         $item->footerCategories = $categoryNames;
//     } else {
//         $item->footerCategories = [];
//     }

//     return $item;
// });
    

//         return $this->sendResponse($landingPageInfo, 'Landing Page Info fetched successfully.');
//     }


public function getLandingPageInfo()
{
    $landingPageInfo = DB::table('landing_pages_info')
        ->select(
            'landing_pages_info.favicon',
            'landing_pages_info.notice',
            'landing_pages_info.topBanner',
            'landing_pages_info.bottomBanner',
            'landing_pages_info.logo',
            'landing_pages_info.facebook',
            'landing_pages_info.linkedin',
            'landing_pages_info.instagram',
            'landing_pages_info.whatsapp',
            'landing_pages_info.pinterest',
            'landing_pages_info.copyrightText',
            'landing_pages_info.footerCategories',// Select the raw JSON string
            'landing_pages_info.name',
            'landing_pages_info.companyName',
            'landing_pages_info.storeName',
            'landing_pages_info.address',
            'landing_pages_info.email',
            'landing_pages_info.phone',

        )
        ->first();

    // Check if $landingPageInfo exists before setting properties
    if ($landingPageInfo) {
        // Decode the footerCategories field if it exists, otherwise set it to an empty array
        $landingPageInfo->footerCategories = $landingPageInfo->footerCategories
            ? json_decode($landingPageInfo->footerCategories, true)
            : [];
    } else {
        // Return an empty object or null response if no data is found
        $landingPageInfo = '';
    }

    return $this->sendResponse($landingPageInfo, 'Landing Page Info fetched successfully.');
}

}
