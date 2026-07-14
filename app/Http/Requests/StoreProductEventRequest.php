<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductEventRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => [
                'required',
                'uuid',
            ],

            'visitor_id' => [
                'required',
                'uuid',
            ],

            'session_id' => [
                'required',
                'uuid',
            ],

            'event_name' => [
                'required',
                'string',
                Rule::in(array_keys(config('product_analytics.events'))),
            ],

            'properties' => [
                'nullable',
                'array',
                'max:20',
            ],

            'path' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
