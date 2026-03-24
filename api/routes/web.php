<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    //customization
    //Route::view('/customization', 'customization')->name('customization');

    //match history
    Route::view('/matches/history', 'matches.history')->name('matches.history');

    //leaderboard
    Route::view('/leaderboard', 'leaderboard')->name('leaderboard');

    //store
    //Route::view('/store', 'store')->name('store');
});
