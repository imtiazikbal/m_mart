<?php

namespace App\Http\Controllers\api\v1;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class QuestionAnswerController extends ResponseController  
{
    //storeQuestion 
    public function storeQuestion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'question' => 'required|string|max:255',
                'productId' => 'required',
                ]);
            if ($validator->fails()) {
                return $this->sendError('Validation error', $validator->errors());
            }
            $userId = $request->headers->get('userID');
            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) {
                return $this->sendError('User not found', [], 404);
            }
            DB::table('ask_questions')->insert([
                'userId' => $userId,
                'question' => $request->question,
                'productId' => $request->productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
           return $this->sendResponse('Question and Answer created successfully', 'Question and Answer created successfully');
        } catch (Exception $e) {
            return $this->sendError('Error creating question', [], 500);
        }
    }

    // answerQuestion 
    public function answerQuestion(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'answer' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation error', $validator->errors());
            }




            $questionId = $request->id;
            $findQuestion = DB::table('ask_questions')->where('id',$questionId)->first();
            if (!$findQuestion) {
                return $this->sendError('Question not found', [], 404);
            }

            DB::table('ask_questions')->where('id', $questionId)->update([
                'answer' => $request->answer,
            ]);

            return $this->sendResponse('Answer created successfully', 'Answer created successfully');
        } catch (Exception $e) {
            return $this->sendError('Error creating answer', [], 500);
        }
    }


    /// getAllQuestion 
    public function getAllQuestion(Request $request)
    {
        try {
           
            $questions = DB::table('ask_questions')
             ->join('users', 'ask_questions.userId', '=', 'users.id')
             ->join('products', 'ask_questions.productId', '=', 'products.id')
                ->select(
                'ask_questions.id',
                        'ask_questions.productId',
                        'products.title as productTitle',
                        'products.displayImageSrc as productThumbnailSrc',
                        DB::raw("CONCAT(users.firstName, ' ', users.lastName) as customerName"),
                        'users.email as customerEmail',
                        'ask_questions.question'
    )
    ->get();

    $modifiedData = $questions->map(function ($item) {
        return [
            'id' => (string) $item->id,
            'productId' => $item->productId,
            'productTitle' => $item->productTitle,
            'productThumbnailSrc' => $item->productThumbnailSrc,
            'customerName' => $item->customerName,
            'customerEmail' => $item->customerEmail,
            'question' => $item->question,
        ];
    });
            return $this->sendResponse($questions, 'Questions retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError('Error retrieving questions', [], 500);
        }
    }


    // deleteFaq 
    public function deleteFaq(Request $request,$id){
        try{
           
            $findQuestion = DB::table('ask_questions')->where('id',$id)->first();
            if (!$findQuestion) {
                return $this->sendError('Faq not found', [], 404);
            }
            DB::table('ask_questions')->where('id', $id)->delete();
            return $this->sendResponse('Question deleted successfully', 'Question deleted successfully');
        }catch(Exception $e){
            return $this->sendError('', $e->getMessage(),0);
        }
    }

    // getAllQuestionForAdmin 
    public function getAllQuestionForAdmin(Request $request){

        try {
           
            $questions = DB::table('ask_questions')
             ->join('users', 'ask_questions.userId', '=', 'users.id')
             ->leftJoin('products', 'ask_questions.productId', '=', 'products.id')
                ->select(
                'ask_questions.id as id',
                        'ask_questions.productId',
                        'products.title as productTitle',
                        'products.displayImageSrc as productThumbnailSrc',
                        DB::raw("CONCAT(users.firstName, ' ', users.lastName) as customerName"),
                        'users.email as customerEmail',
                        'ask_questions.question',
                        'ask_questions.created_at as date',
                        'ask_questions.answer'
    )
    ->get();


    $modifiedData = $questions->map(function ($item) {
        return [
            'id' => (string) $item->id,
            'productId' => (string) $item->productId,
            'productTitle' => (string) $item->productTitle,
            'productThumbnailSrc' => (string) $item->productThumbnailSrc,
            'customerName' => (string) $item->customerName,
            'customerEmail' => (string) $item->customerEmail,
            'question' => (string) $item->question,
            'answer' => (string) $item->answer,
            'date' => (string) $item->date
        ];
    });

            return $this->sendResponse($modifiedData, 'Questions retrieved successfully');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
        
    
}

}