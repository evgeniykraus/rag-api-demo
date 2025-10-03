<?php

use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\ProposalController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'v1'], function () {
    Route::apiResource('proposals', ProposalController::class)->where(['proposal' => '[0-9]+']);

    Route::group(['prefix' => 'proposals'], function () {
        Route::get('search', [ProposalController::class, 'search']);
        Route::get('{proposal}/similar', [ProposalController::class, 'similar']);
    });

    Route::group(['prefix' => 'dictionary'], function () {
        Route::get('cities', [DictionaryController::class, 'cities']);
        Route::get('categories', [DictionaryController::class, 'categories']);
    });
});


