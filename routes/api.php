<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');




Route::get('/test-1', function () {
    return response()->json(['message' => 'Hello, World!']);
});



Route::middleware("auth:sanctum")->group(function () {

    Route::post("/logout", [AuthController::class, "logout"]);

    Route::get('/test', function () {
        return response()->json(['message' => 'Hello, World! Login']);
    });
});
