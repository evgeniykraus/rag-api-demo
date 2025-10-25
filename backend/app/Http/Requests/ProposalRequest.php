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
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,bmp,png',
                'max:10240' // 10MB
            ],
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
            "attachments" => [
                "example" => ["file1.jpg", "file2.pdf"],
                "description" => "Прикрепленные файлы (максимум 5 файлов, до 10MB каждый)",
                "type" => "array",
                "required" => false
            ],
        ];
    }
}
