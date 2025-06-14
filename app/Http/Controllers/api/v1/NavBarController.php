<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class NavBarController extends ResponseController
{
    // getAllNavItems
    public function getAllNavItems(Request $request)
{
    try {
        $categories = DB::table('categories')
            ->leftJoin('sub_categories', 'categories.id', '=', 'sub_categories.categoryId')
            ->select('categories.id as categoryId', 'categories.name as categoryName', 'sub_categories.name as subCategoryName')
            ->get();

        // Group data by category
        $navItems = [];
        foreach ($categories as $item) {
            $catName = $item->categoryName;
            if (!isset($navItems[$catName])) {
                $navItems[$catName] = [
                    'categoryName' => $catName,
                    'subCategories' => [],
                ];
            }

            if ($item->subCategoryName) {
                $navItems[$catName]['subCategories'][] = $item->subCategoryName;
            }
        }

        // Re-index numerically
        $navItems = array_values($navItems);

        return $this->sendResponse($navItems, 'Nav items fetched successfully');
    } catch (Exception $e) {
        return $this->sendError('Something went wrong', [], 500);
    }
}

}
