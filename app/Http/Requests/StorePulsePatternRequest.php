<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePulsePatternRequest extends FormRequest
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
                $numerator = $this->integer('numerator');

                $grouping = $this->input(
                    'grouping',
                    []
                );

                $pattern = $this->input(
                    'pattern',
                    []
                );

                if (
                    array_sum($grouping)
                    !== $numerator
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'grouping',
                            'Grouping must match numerator.'
                        );
                }

                if (
                    count($pattern)
                    !== $numerator
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
