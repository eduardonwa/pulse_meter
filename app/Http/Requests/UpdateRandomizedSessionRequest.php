<?php

namespace App\Http\Requests;

class UpdateRandomizedSessionRequest extends StoreRandomizedSessionRequest
{
    public function rules(): array
    {
        if ($this->isRenameOnly()) {
            return [
                'name' => ['required', 'string', 'max:255'],
            ];
        }

        return parent::rules();
    }

    public function after(): array
    {
        return $this->isRenameOnly()
            ? []
            : parent::after();
    }

    private function isRenameOnly(): bool
    {
        return $this->has('name')
            && count($this->all()) === 1;
    }
}
