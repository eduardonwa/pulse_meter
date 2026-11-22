<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePulsePresetRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'numerator' => [
                'required',
                'integer',
                'min:1',
                'max:16',
            ],

            'denominator' => [
                'required',
                'integer',
                Rule::in([
                    2,
                    4,
                    8,
                    16
                ])
            ],

            'grouping' => [
                'required',
                'array',
                'min:1',
            ],

            'grouping.*' => [
                'required',
                'numeric',
                'min:0.0625',
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

            'pattern.*.subdivisions' => [
                'sometimes',
                'array',
            ],

            'pattern.*.subdivisions.*.label' => [
                'required',
                'string',
                Rule::in([
                    'e',
                    '&',
                    'a',
                ]),
            ],

            'pattern.*.subdivisions.*.groupStart' => [
                'sometimes',
                'boolean',
            ],

            'pattern.*.subdivisions.*.sound' => [
                'required',
                Rule::in([
                    'accent',
                    'click',
                    'rest',
                ]),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $numerator = $this->integer('numerator');
                $grouping = $this->input('grouping', []);
                $pattern = $this->input('pattern', []);

                if (is_array($grouping)
                    && abs(array_sum($grouping) - $numerator) > 0.000001
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'grouping',
                            'Grouping must match numerator.'
                        );
                }

                if (is_array($pattern)
                    && count($pattern) !== $numerator
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'pattern',
                            'Pattern must match numerator.'
                        );
                }
            },
        ];
    }
}
