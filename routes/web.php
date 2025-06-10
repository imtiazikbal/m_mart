<?php

use App\Http\Controllers\api\v1\AdminController;
use App\Http\Controllers\api\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [AuthController::class, 'userRegistration']);
Route::get('/login', [AuthController::class, 'userLogin']);

Route::get('authUser', [AuthController::class,'tokenVarification'])->name('token')->middleware('api.auth');
Route::get('adminUser', [AdminController::class,'tokenVarificationForAdmin'])->middleware('api.auth');



Route::get('signup', [AdminController::class,'signup']);
Route::get('loginAdmin', [AdminController::class,'login']);