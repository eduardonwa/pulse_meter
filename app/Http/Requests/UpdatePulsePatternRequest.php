<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePulsePatternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'numerator' => [
                'required',
                'integer',
                'min:1',
                'max:255',
            ],

            'denominator' => [
                'required',
                'integer',
                'min:1',
                'max:255',
            ],

            'grouping' => [
                'required',
                'array',
                'min:1',
            ],

            'grouping.*' => [
                'required',
                'integer',
                'min:1',
            ],

            'pattern' => [
                'required',
                'array',
                'min:1',
            ],

            'pattern.*.sound' => [
                'required',
                Rule::in([
                    'accent',
                    'click',
                    'rest',
                ]),
            ],

            'pattern.*.groupStart' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $grouping = $this->input('grouping', []);
                $numerator = $this->integer('numerator');
                $pattern = $this->input('pattern', []);

                if (array_sum($grouping) !== $numerator) {
                    $validator->errors()->add(
                        'grouping',
                        'The grouping total must match the numerator.'
                    );
                }

                if (count($pattern) !== $numerator) {
                    $validator->errors()->add(
                        'pattern',
                        'The pattern length must match the numerator.'
                    );
                }
            },
        ];
    }
}