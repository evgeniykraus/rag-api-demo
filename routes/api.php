<?php

use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ProposalController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'v1'], function () {
    Route::apiResource('proposals', ProposalController::class)->where(['proposal' => '[0-9]+']);

    Route::group(['prefix' => 'proposals'], function () {
        Route::get('search', [ProposalController::class, 'search']);

        Route::group(['prefix' => '{proposal}', 'where' => ['proposal' => '[0-9]+']], function () {
            Route::get('similar', [ProposalController::class, 'similar']);

            Route::group(['prefix' => 'response'], function () {
                Route::post('', [ProposalController::class, 'storeResponse']);
                Route::get('ai-generate', [ProposalController::class, 'generateResponse']);
            });
        });


    });

    Route::group(['prefix' => 'dictionary'], function () {
        Route::get('cities', [DictionaryController::class, 'cities']);
        Route::get('categories', [DictionaryController::class, 'categories']);
    });

    Route::group(['prefix' => 'analytics'], function () {
        Route::get('overview', [AnalyticsController::class, 'overview']);
        Route::get('by-period', [AnalyticsController::class, 'byPeriod']);
        Route::get('by-category', [AnalyticsController::class, 'byCategory']);
        Route::get('by-city', [AnalyticsController::class, 'byCity']);
    });
});


