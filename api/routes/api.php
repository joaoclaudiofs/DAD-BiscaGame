<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\CustomizationsController;
use App\Http\Controllers\ScoreboardController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\CoinsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//todos (anonimos, jogadores, admins)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/scoreboard', [ScoreboardController::class, 'index']);
Route::get('/statistics/public', [StatisticsController::class, 'public']);

// autenticados (jogadores, admins)
Route::middleware('auth:sanctum')->group(function () {
    //autenticados (jogadores e admins)
    Route::post('logout', [AuthController::class, 'logout']);  
    Route::get('/users/me', function (Request $request) { return $request->user();});
    Route::get('/coins/transaction-types', [CoinsController::class, 'transactionTypes']);
    Route::post('/users/me', [UserController::class, 'updateProfile']);
    Route::put('/users/me/password', [UserController::class, 'updatePassword']);
    Route::delete('/users/me', [UserController::class, 'deleteAccount']);
    Route::get('/users/me/matches', function (Request $request) {
        $request->merge(['mine' => 1]);
        return app(MatchController::class)->index($request);
    });
    Route::prefix('files')->group(function () {
        Route::post('userphoto', [FileController::class, 'uploadUserPhoto']);
        //Route::post('cardfaces', [FileController::class, 'uploadCardFaces']);
    });

    Route::apiResource('games', GameController::class)->only(['index', 'show', 'store', 'update']);
    Route::apiResource('matches', MatchController::class)->only(['index', 'show', 'store', 'update']);
    Route::get('/matches/{match}/games', [MatchController::class, 'games']);


    //apenas jogadores
    Route::middleware('can:player-actions')->prefix('users')->group(function () {
        Route::post('/coins/add', [UserController::class, 'addCoins']);
        Route::post('/coins/remove', [UserController::class, 'removeCoins']);
        Route::post('/coins/purchase', [CoinsController::class, 'purchase']);
        Route::get('/me/coins', [CoinsController::class, 'balance']);
        Route::get('/me/coins/transactions', [CoinsController::class, 'myTransactions']);
        Route::get('/me/stats', [UserController::class, 'myStats']);
        /*Route::get('/me/deck', [UserController::class, 'getEquippedDeck']);
        Route::get('/customizations/owned', [UserController::class, 'getOwnedCustomizations']);
        Route::get('/customizations/{type}', [CustomizationsController::class, 'index']);
        Route::get('/customizations/{customization}/owned', [CustomizationsController::class, 'hasCustomization']);
        Route::post('/customizations/{customization}/purchase', [UserController::class, 'purchaseCustomization']);
        Route::post('/customizations/{customization}/equip', [UserController::class, 'equipCustomization']);*/
    });
   
    //apenas admins
    Route::middleware('can:admin-actions')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/statistics/admin', [StatisticsController::class, 'admin']);
        Route::prefix('admin')->group(function () {
            Route::post('/users', [AdminController::class, 'createAdmin']);
            Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
            Route::post('/users/{user}/block', [AdminController::class, 'blockUser']);
            Route::post('/users/{user}/unblock', [AdminController::class, 'unblockUser']);
            Route::get('/users/{user}/stats', [UserController::class, 'userStats']);
            Route::get('/coins/transactions', [CoinsController::class, 'allTransactions']);
        });
    });
});
