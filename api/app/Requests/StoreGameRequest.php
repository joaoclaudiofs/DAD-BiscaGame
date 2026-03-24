<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGameRequest extends FormRequest
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
            'player1_user_id' => [
                'integer'
            ],
            'type' => ['required', Rule::in(['3', '9'])],
            'status' => ['required', Rule::in(['Pending', 'Playing', 'Ended', 'Interrupted'])],
            'player2_user_id' => [
                'nullable',
                'different:player1_user_id',
            ],
            'player1_points' => ['nullable', 'integer'],
            'player2_points' => ['nullable', 'integer'],
            'began_at' => 'required|date',
            'ended_at' => 'nullable|date',
            'total_time' => 'nullable|integer',
            'winner_user_id' => 'nullable',
            'loser_user_id' => 'nullable',
            'is_draw' => 'nullable|integer',
            'match_id' => 'nullable|exists:matches,id',
        ];
    }

    /**
     * Get the validation messages for invalid fields.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'player1_user_id.integer' => 'Creator ID must be an integer.',
            'player1_user_id.exists' => 'The selected player does not exist.',
            'type.required' => 'Game type is required.',
            'type.in' => 'Game type must be either 3 or 9.',
            'status.required' => 'Game status is required.',
            'status.in' => 'Game status must be one of: Pending, Playing, Ended, Interrupted.',
            'player2_user_id.required_if' => 'Player 2 is required for multiplayer games.',
            'player2_user_id.exists' => 'The selected player does not exist.',
            'player2_user_id.different' => 'Player 2 must be different from the creator.',
        ];
    }
}
