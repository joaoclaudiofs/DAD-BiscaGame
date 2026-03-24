<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatchRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['3', '9'])],

            'player1_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'player2_user_id' => ['nullable', 'integer', 'different:player1_user_id', 'exists:users,id'],

            'status' => ['required', Rule::in(['Playing'])],
            'began_at' => ['required', 'date'],
            'stake' => ['nullable', 'integer'],
        ];
    }
}
