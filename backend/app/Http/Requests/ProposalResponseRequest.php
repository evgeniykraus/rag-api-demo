<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposalResponseRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:3', 'max:4000'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'content' => [
                'example' => 'Ответ на обращение',
                'description' => 'Ответ на обращение',
                'type' => 'string',
                'required' => true
            ],
        ];
    }
}
