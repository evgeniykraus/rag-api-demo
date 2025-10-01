<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryTreeResource;
use App\Http\Resources\CityResource;
use App\Repositories\DictionaryRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DictionaryController extends Controller
{
    public function __construct(
        private readonly DictionaryRepository $dictionaryRepository
    )
    {
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function cities(): AnonymousResourceCollection
    {
        CityResource::withoutWrapping();

        return CityResource::collection(
            $this->dictionaryRepository->cities()
        );
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function categories(): AnonymousResourceCollection
    {
        CategoryTreeResource::withoutWrapping();

        return CategoryTreeResource::collection(
            $this->dictionaryRepository->categories()
        );
    }
}
