<?php

return [

    'middleware' => [
        'api_key_invalid' => 'Неверный API ключ',
        'api_key_required' => 'Заголовок x-api-key обязателен для доступа к API',
    ],
    'embedding' => [
        'unexpected_dimension_index' => 'Несоответствие размерности эмбеддинга на индексе %s (ожидалось %s, получено %s)',
        'unexpected_dimension' => 'Несоответствие размерности эмбеддинга (ожидалось %s, получено %s)',
        'proposal_build' => 'Ошибка при создании вектора для обращения. %s',
    ],
];
