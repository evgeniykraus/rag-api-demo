<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $cities = [
            'Междуреченск',
            'Кемерово',
            'Новокузнецк',
            'Анжеро-Судженск',
            'Белово',
            'Берёзовский',
            'Гурьевск',
            'Калтан',
            'Киселёвск',
            'Ленинск-Кузнецкий',
            'Мариинск',
            'Мыски',
            'Осинники',
            'Полысаево',
            'Прокопьевск',
            'Салаир',
            'Тайга',
            'Таштагол',
            'Топки',
            'Юрга',

        ];

        foreach ($cities as $city) {
            City::query()->create(['name' => $city]);
        }
    }
}
