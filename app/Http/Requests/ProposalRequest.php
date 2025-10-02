<?php

namespace App\Http\Requests;

use App\Rules\NotMeaninglessTextRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProposalRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'city_id' => ['required', 'exists:cities,id'],
            'content' => ['required', 'string', 'max:4000'],
//            'content' => ['required', 'string', 'max:4000', new NotMeaninglessTextRule()],
        ];
    }

    /**
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            "city_id" => [
                "example" => 1,
                "description" => "ID города",
                "type" => "integer",
                "required" => true
            ],
            "content" => [
                "example" => "Текст обращения", // Кириллица без escape
                "description" => "Содержание обращения",
                "type" => "string",
                "required" => true,
                "maxLength" => 1000
            ],
        ];
    }
}
