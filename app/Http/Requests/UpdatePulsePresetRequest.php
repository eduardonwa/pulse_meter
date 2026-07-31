<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePulsePresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'numerator' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:16',
            ],

            'denominator' => [
                'sometimes',
                'required',
                'integer',
                Rule::in([
                    2,
                    4,
                    8,
                    16,
                ]),
            ],

            'grouping' => [
                'sometimes',
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
                'sometimes',
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
                // A rename-only request has no musical data to validate.
                if (
                    !$this->has('numerator')
                    && !$this->has('grouping')
                    && !$this->has('pattern')
                ) {
                    return;
                }

                $numerator = $this->integer('numerator');
                $grouping = $this->input('grouping', []);
                $pattern = $this->input('pattern', []);

                if (
                    is_array($grouping)
                    && array_sum($grouping) !== $numerator
                ) {
                    $validator->errors()->add(
                        'grouping',
                        'The grouping total must match the numerator.'
                    );
                }

                if (
                    is_array($pattern)
                    && count($pattern) !== $numerator
                ) {
                    $validator->errors()->add(
                        'pattern',
                        'The pattern length must match the numerator.'
                    );
                }
            },
        ];
    }
}