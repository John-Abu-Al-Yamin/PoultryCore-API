<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarnController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\SupplierController;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



// AUTHENTICATION 
Route::post('/register', [AuthController::class, "register"]);
Route::post('/login', [AuthController::class, "login"]);





Route::middleware("auth:sanctum")->group(function () {

    Route::post("/logout", [AuthController::class, "logout"]);
    Route::get("/user", [AuthController::class, "user"]);

    // BARN ROUTES
    Route::get("/barns", [BarnController::class, "index"]);
    Route::post("/barns", [BarnController::class, "store"]);
    Route::get("/barns/{id}", [BarnController::class, "show"]);
    Route::put("/barns/{id}", [BarnController::class, "update"]);
    Route::delete("/barns/{id}", [BarnController::class, "destroy"]);

    // BATCH ROUTES
    Route::get("/batches", [BatchController::class, "index"]);
    Route::post("/batches", [BatchController::class, "store"]);
    Route::get("/batches/{id}", [BatchController::class, "show"]);
    Route::put("/batches/{id}", [BatchController::class, "update"]);
    Route::delete("/batches/{id}", [BatchController::class, "destroy"]);
    Route::post("/batches/{id}/close", [BatchController::class, "close"]);

    // SUPPLIER ROUTES
    Route::get("/suppliers", [SupplierController::class, "index"]);
    Route::post("/suppliers", [SupplierController::class, "store"]);
    Route::get("/suppliers/{id}", [SupplierController::class, "show"]);
    Route::put("/suppliers/{id}", [SupplierController::class, "update"]);
    Route::delete("/suppliers/{id}", [SupplierController::class, "destroy"]);
});
