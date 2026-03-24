<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchRequest;
use App\Http\Requests\UpdateMatchRequest;
use App\Models\Game;
use App\Models\Matches;
use App\Models\Notification;
use App\Models\User;
use App\Http\Controllers\ScoreboardController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Termwind\Components\Li;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $query = Matches::with(['player1:id,name,nickname', 'player2:id,name,nickname', 'games']);

        $user = $request->user();
        $isAdmin = $user && $user->type === 'A';

        if ($isAdmin && $request->filled('user_id')) {
            //admin visualiza o histórico de um utilizador específico
            $targetUserId = (int) $request->input('user_id');
            $query->where(function ($q) use ($targetUserId) {
                $q->where('player1_user_id', $targetUserId)
                  ->orWhere('player2_user_id', $targetUserId);
            });
        } elseif (!$isAdmin) {
            //jogador: só pode ver o seu próprio histórico
            if ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('player1_user_id', $user->id)
                      ->orWhere('player2_user_id', $user->id);
                });
            }
        }

        //filtro pelo estado
        if ($request->filled('status') && $request->input('status') !== 'All') {
            $status = $request->input('status');
            if (in_array($status, ['Pending', 'Playing', 'Ended', 'Interrupted'])) {
                $query->where('status', $status);
            }
        }


        //filtro pela data de início
        if ($request->filled('began_after')) {
            $beganAfter = $request->input('began_after');
            $query->where('began_at', '>=', $beganAfter);
        }
        if ($request->filled('began_before')) {
            $beganBefore = $request->input('began_before');
            $query->where('began_at', '<=', $beganBefore);
        }
        //filtro pela data de fim
        if ($request->filled('ended_after')) {
            $endedAfter = $request->input('ended_after');
            $query->where('ended_at', '>=', $endedAfter);
        }
        if ($request->filled('ended_before')) {
            $endedBefore = $request->input('ended_before');
            $query->where('ended_at', '<=', $endedBefore);
        }

        //filtro pelo nome/nickname/email do jogador (player1 ou player2)
        if ($request->filled('player_name')) {
            $playerName = '%' . $request->input('player_name') . '%';
            $query->where(function ($q) use ($playerName) {
                $q->whereHas('player1', function ($sq) use ($playerName) {
                    $sq->where('name', 'like', $playerName)
                      ->orWhere('nickname', 'like', $playerName)
                      ->orWhere('email', 'like', $playerName);
                })
                ->orWhereHas('player2', function ($sq) use ($playerName) {
                    $sq->where('name', 'like', $playerName)
                      ->orWhere('nickname', 'like', $playerName)
                      ->orWhere('email', 'like', $playerName);
                });
            });
        }

        //filtro por capote
        if ($request->filled('capote')) {
            $query->whereHas('games', function ($q) {
                $q->where(function ($inner) {
                    $inner->whereRaw('player1_points >= 91 AND player1_points < 120')
                          ->orWhereRaw('player2_points >= 91 AND player2_points < 120');
                });
            });
        }

        //filtro por bandeira
        if ($request->filled('bandeira')) {
            $query->whereHas('games', function ($q) {
                $q->where(function ($inner) {
                    $inner->whereRaw('player1_points = 120')
                          ->orWhereRaw('player2_points = 120');
                });
            });
        }

        //filtro pelo número mínimo de jogos
        if ($request->filled('min_games')) {
            $minGames = (int) $request->input('min_games');
            if ($minGames > 0) {
                $query->has('games', '>=', $minGames);
            }
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

        $perPage = (int) $request->input('per_page', 15);
        $matches = $query->paginate($perPage);

        //mapear resultados e calcular agregados dos jogos relacionados (sem empates)
        $items = $matches->getCollection()->map(function ($match) use ($request) {
            $user = $request->user();
            $isPlayer1 = $user && ($match->player1_user_id == $user->id);

            $games = $match->games ?? collect();

            //total de pontos de jogador para cada jogador
            $totalP1Points = (int) $games->sum('player1_points');
            $totalP2Points = (int) $games->sum('player2_points');

            //total de marcas de partida para cada jogador
            $matchP1Marks = (int) ($match->player1_marks ?? 0);
            $matchP2Marks = (int) ($match->player2_marks ?? 0);

            $winnerId = $match->winner_user_id;

            $result = null;
            if ($user) {
                //verifica se ficou interrompida
                if ($match->status === 'Interrupted') {
                    $result = 'interrupted';
                }
                //verifica se houve um empate
                elseif ($match->status === 'Ended' && !$winnerId) {
                    $result = 'draw';
                }
                //verifica se houve uma vitória ou derrota normal
                elseif ($winnerId) {
                    $result = ($winnerId == $user->id) ? 'win' : 'loss';
                }
            }

            //verifica se a partida teve capote ou bandeira
            $hasCapote = $games->contains(function ($game) {
                $p1Points = (int) $game->player1_points;
                $p2Points = (int) $game->player2_points;
                $maxPoints = max($p1Points, $p2Points);
                return $maxPoints >= 91;
            });

             $hasBandeira = $games->contains(function ($game) {
                $p1Points = (int) $game->player1_points;
                $p2Points = (int) $game->player2_points;
                $maxPoints = max($p1Points, $p2Points);
                return $maxPoints >= 120;
            });

            $total_games = $games->count();

            return [
                'id' => $match->id,
                'ended_at' => $match->ended_at,
                'result' => $result,
                'status' => $match->status,
                'player1_id' => $match->player1_user_id,
                'player2_id' => $match->player2_user_id,
                'player1_name' => $match->player1?->nickname ?? $match->player1?->name ?? 'Bot',
                'player2_name' => $match->player2?->nickname ?? $match->player2?->name ?? 'Bot',
                'winner_user_id' => $winnerId,
                'player1_points' => $totalP1Points,
                'player2_points' => $totalP2Points,
                'player1_marks' => $matchP1Marks,
                'player2_marks' => $matchP2Marks,
                'has_capote' => $hasCapote,
                'has_bandeira' => $hasBandeira,
                'total_games' => $total_games,
                'opponent_name' => $isPlayer1
                    ? ($match->player2?->nickname ?? $match->player2?->name ?? 'Bot')
                    : ($match->player1?->nickname ?? $match->player1?->name ?? 'Bot'),
            ];
        })->toArray();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $matches->currentPage(),
                'last_page' => $matches->lastPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total()
            ]
        ]);
    }

    public function store(StoreMatchRequest $request)
    {
        $validatedData = $request->validated();
        $match = Matches::create($validatedData);
        return response()->json($match, 201);
    }

    public function show(Game $game)
    {
        //
    }

    public function update(UpdateMatchRequest $request, Matches $match)
    {
        $validatedData = $request->validated();

        //obter o jogador líder anterior antes de atualizar a partida
        $previousLeader = ScoreboardController::getTopWinsLeader();

        $match->update($validatedData);

        //verificar se a partida acabou de terminar com um vencedor
        if (isset($validatedData['winner_user_id']) && $validatedData['winner_user_id']) {
            $winnerId = $validatedData['winner_user_id'];

            //contar total de partidas completadas para este utilizador
            $totalMatches = Matches::where('winner_user_id', $winnerId)
                ->where('status', 'Ended')
                ->count();
            Log::info("User $winnerId has $totalMatches total completed matches.");

            //verificar se o líder do scoreboard mudou
            $newLeader = ScoreboardController::getTopWinsLeader();
            Log::info("Previous leader ID: " . ($previousLeader ? $previousLeader->id : 'None'));
            Log::info("New leader ID: " . ($newLeader ? $newLeader->id : 'None'));
            //se há um novo líder e é diferente do anterior
            if ($newLeader && $previousLeader && $newLeader->id !== $previousLeader->id) {
                $leaderName = $newLeader->nickname ?? $newLeader->name;

                Notification::create([
                    'type' => 'scoreboard_leader',
                    'title' => 'New Leaderboard Leader!',
                    'message' => "{$leaderName} is now #1 on the leaderboard!",
                    'route' => '/scoreboard',
                    'data' => json_encode([
                        'new_leader_id' => $newLeader->id,
                        'new_leader_name' => $leaderName,
                        'wins' => (int) $newLeader->wins
                    ]),
                    'is_global' => true,
                    'created_at' => now(),
                ]);

                Log::info("New leaderboard leader: {$leaderName} (ID: {$newLeader->id}) with {$newLeader->wins} wins");
            }
        }

        return response()->json($match, 201);
    }

    public function destroy(Game $game)
    {
        //
    }

    //obter os jogos de uma partida
    public function games(Matches $match)
    {
        $user = request()->user();

        //registos detalhados são visíveis apenas para administradores e para jogadores que participaram na partida
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $isAdmin = $user->type === 'A';
        $isParticipant = in_array($user->id, [$match->player1_user_id, $match->player2_user_id]);

        if (!$isAdmin && !$isParticipant) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($match->games()->get());
    }
}
