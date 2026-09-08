<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRandomizedSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bpm' => ['required', 'integer', 'min:30', 'max:400'],
            'root' => ['required', Rule::in([
                'C', 'Db', 'D', 'Eb', 'E', 'F',
                'Gb', 'G', 'Ab', 'A', 'Bb', 'B',
            ])],
            'scale' => ['required', Rule::in([
                'ionian', 'dorian', 'phrygian', 'lydian',
                'mixolydian', 'aeolian', 'locrian',
                'harmonic-minor', 'melodic-minor', 'melodic-major',
            ])],
            'playback_mode' => ['required', Rule::in(['click', 'pulse'])],
            'numerator' => ['required', 'integer', 'min:1', 'max:16'],
            'denominator' => ['required', 'integer', Rule::in([2, 4, 8, 16])],
            'subdivision' => ['required', 'integer', Rule::in([1, 2, 4])],
            'grouping' => ['required', 'array', 'min:1'],
            'grouping.*' => ['required', 'integer', 'min:1'],
            'pattern' => ['required', 'array', 'min:1'],
            'pattern.*.sound' => ['required', Rule::in(['accent', 'click', 'rest'])],
            'pattern.*.groupStart' => ['required', 'boolean'],
            'pattern.*.subdivisions' => ['sometimes', 'array'],
            'pattern.*.subdivisions.*.label' => [
                'required', Rule::in(['e', '&', 'a']),
            ],
            'pattern.*.subdivisions.*.sound' => [
                'required', Rule::in(['accent', 'click', 'rest']),
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

                if (is_array($grouping) && array_sum($grouping) !== $numerator) {
                    $validator->errors()->add(
                        'grouping',
                        'Grouping must match numerator.'
                    );
                }

                if (is_array($pattern) && count($pattern) !== $numerator) {
                    $validator->errors()->add(
                        'pattern',
                        'Pattern must match numerator.'
                    );
                }

                if (
                    is_array($pattern)
                    && ($pattern[0]['groupStart'] ?? false) !== true
                ) {
                    $validator->errors()->add(
                        'pattern.0.groupStart',
                        'The first beat must start a group.'
                    );
                }
            },
        ];
    }
}
