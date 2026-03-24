<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Game::query()->with(['winner']);

        //apenas jogos dos matches do user (se não for admin)
        $user = $request->user();
        if ($user && $user->type !== 'A') {
            $query->whereHas('match', function ($q) use ($user) {
                $q->where(function ($inner) use ($user) {
                    $inner->where('player1_user_id', $user->id)
                        ->orWhere('player2_user_id', $user->id);
                });
            });
        }

        if ($request->has('type') && in_array($request->type, ['3', '9'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && in_array($request->status, ['Pending', 'Playing', 'Ended', 'Interrupted'])) {
            $query->where('status', $request->status);
        }


        $sortField = $request->input('sort_by', 'began_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSortFields = [
            'began_at',
            'ended_at',
            'total_time',
            'type',
            'status'
        ];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->input('per_page', 15);
        $games = $query->paginate($perPage);

        return response()->json([
            'data' => $games->items(),
            'meta' => [
                'current_page' => $games->currentPage(),
                'last_page' => $games->lastPage(),
                'per_page' => $games->perPage(),
                'total' => $games->total()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'            => ['required', Rule::in(['3', '9'])],
            'player1_user_id' => ['required', 'integer', 'exists:users,id'],
            'player2_user_id' => ['required', 'integer', 'different:player1_user_id', 'exists:users,id'],
            'is_draw'         => ['required', 'boolean'],
            'winner_user_id'  => ['nullable', 'integer', 'exists:users,id'],
            'loser_user_id'   => ['nullable', 'integer', 'exists:users,id'],
            'match_id'        => ['required', 'integer', 'exists:matches,id'],
            'status'          => ['required', Rule::in(['Pending', 'Playing', 'Ended', 'Interrupted'])],
            'began_at'        => ['required', 'date'],
            'ended_at'        => ['nullable', 'date'],
            'total_time'      => ['nullable', 'numeric'],
            'player1_points'  => ['nullable', 'integer'],
            'player2_points'  => ['nullable', 'integer'],
        ]);

        $game = Game::create($data);
        return response()->json($game, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        $user = request()->user();
        //verifca se o user é admin ou participou no match deste jogo
        if ($user && $user->type !== 'A') {
            $matchGame = $game->match;
            if (!$matchGame || ($matchGame->player1_user_id !== $user->id && $matchGame->player2_user_id !== $user->id)) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        return response()->json($game);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Game $game)
    {
        $data = $request->validate([
            'type'            => ['sometimes', Rule::in(['3', '9'])],
            'player1_user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'player2_user_id' => ['sometimes', 'integer', 'different:player1_user_id', 'exists:users,id'],
            'is_draw'         => ['sometimes', 'boolean'],
            'winner_user_id'  => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'loser_user_id'   => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'match_id'        => ['sometimes', 'integer', 'exists:matches,id'],
            'status'          => ['sometimes', Rule::in(['Pending', 'Playing', 'Ended', 'Interrupted'])],
            'began_at'        => ['sometimes', 'date'],
            'ended_at'        => ['sometimes', 'nullable', 'date'],
            'total_time'      => ['sometimes', 'nullable', 'numeric'],
            'player1_points'  => ['sometimes', 'nullable', 'integer'],
            'player2_points'  => ['sometimes', 'nullable', 'integer'],
        ]);

        $game->update($data);

        return response()->json($game);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Game $game)
    {
        //
    }
}
