<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposalSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3'],
        ];
    }

    /**
     * @return array
     */
    public function queryParameters(): array
    {
        return [
            'query' => [
                'example' => 'Не вывозят мусор неделями',
                'type' => 'string',
                'required' => true
            ],
        ];
    }
}
