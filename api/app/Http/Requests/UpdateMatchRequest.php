<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatchRequest extends FormRequest
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
            'ended_at' => ['nullable', 'date'],
            'total_time' => ['nullable', 'integer'],

            'winner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'loser_user_id'  => ['nullable', 'integer', 'exists:users,id'],

            'status' => ['required', Rule::in(['Ended', 'Interrupted', 'Playing'])],
            'player1_marks' => ['nullable', 'integer'],
            'player2_marks' => ['nullable', 'integer'],
            'player1_points' => ['nullable', 'integer'],
            'player2_points' => ['nullable', 'integer'],
        ];
    }
}
