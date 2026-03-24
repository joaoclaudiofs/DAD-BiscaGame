<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Game;
use App\Models\Matches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ScoreboardController extends Controller
{
    private function resolveAvatarUrl(?string $filename): ?string
    {
        $disk = Storage::disk('public');
        if (!$filename) {
            if ($disk->exists('photos_avatars/anonymous.png')) {
                return $disk->url('photos_avatars/anonymous.png');
            }
            return null;
        }
        $candidates = [$filename];
        if (!str_contains($filename, '/')) {
            $candidates[] = 'photos_avatars/'.$filename;
            $candidates[] = 'photos_avatars/'.$filename.'.jpeg';
            $candidates[] = 'photos_avatars/'.$filename.'.jpg';
            $candidates[] = 'photos_avatars/'.$filename.'.png';
        }
        foreach ($candidates as $cand) {
            if ($disk->exists($cand)) {
                return $disk->url($cand);
            }
        }
        if ($disk->exists($filename)) {
            return $disk->url($filename);
        }
        if ($disk->exists('photos_avatars/anonymous.png')) {
            return $disk->url('photos_avatars/anonymous.png');
        }
        if ($disk->exists('photos_avatars/anonymous.jpg')) {
            return $disk->url('photos_avatars/anonymous.jpg');
        }
        return null;
    }


    public static function getTopWinsLeader()
    {
        return User::select('users.id', 'users.name', 'users.nickname')
            ->selectRaw('COUNT(matches.id) as wins, MAX(matches.ended_at) as last_win_at')
            ->leftJoin('matches', function ($join) {
                $join->on('users.id', '=', 'matches.winner_user_id')
                     ->where('matches.status', 'Ended');
            })
            ->whereNull('users.deleted_at')
            ->groupBy('users.id', 'users.name', 'users.nickname')
            ->orderByDesc('wins')
            //se empate no número de vitórias o jogador que ganhou primeiro (data mais antiga) fica em melhor posição
            ->orderBy('last_win_at')
            ->orderBy('users.id')
            ->first();
    }

    public static function getTopCoinsLeader()
    {
        return User::select('id', 'name', 'nickname', 'coins_balance')
            ->whereNull('deleted_at')
            ->orderByDesc('coins_balance')
            ->orderBy('id')
            ->first();
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $currentUser = $request->user();
        $totalUsers = User::whereNull('deleted_at')->count();
        $orderBy = $request->input('order_by', 'wins');
        //depois usar este order by para filtrar por mais vitórias/bandeiras/capotes
        $allowedOrder = ['wins', 'bandeiras', 'capotes'];
        if (!in_array($orderBy, $allowedOrder)) {
            $orderBy = 'wins';
        }

        //agregar bandeiras/capotes por jogador (categorias exclusivas)
        $gameAchievements = DB::table(function ($query) {
            $query->from('games as g1')
                ->select(
                    'g1.player1_user_id as user_id',
                    DB::raw('SUM(CASE WHEN g1.player1_points = 120 THEN 1 ELSE 0 END) as bandeiras'),
                    DB::raw('SUM(CASE WHEN g1.player1_points >= 91 AND g1.player1_points < 120 THEN 1 ELSE 0 END) as capotes')
                )
                ->groupBy('g1.player1_user_id')
                ->unionAll(
                    DB::table('games as g2')
                        ->select(
                            'g2.player2_user_id as user_id',
                            DB::raw('SUM(CASE WHEN g2.player2_points = 120 THEN 1 ELSE 0 END) as bandeiras'),
                            DB::raw('SUM(CASE WHEN g2.player2_points >= 91 AND g2.player2_points < 120 THEN 1 ELSE 0 END) as capotes')
                        )
                        ->groupBy('g2.player2_user_id')
                );
        }, 'raw_game_stats')
            ->select(
                'user_id',
                DB::raw('SUM(bandeiras) as bandeiras'),
                DB::raw('SUM(capotes) as capotes')
            )
            ->groupBy('user_id');

        $winsRanking = User::select(
                'users.id',
                'users.name',
                'users.nickname',
                'users.photo_avatar_filename',
                DB::raw('COALESCE(game_stats.bandeiras, 0) as bandeiras'),
                DB::raw('COALESCE(game_stats.capotes, 0) as capotes')
            )
            ->selectRaw('COUNT(matches.id) as wins, MAX(matches.ended_at) as last_win_at')
            ->leftJoin('matches', function ($join) {
                $join->on('users.id', '=', 'matches.winner_user_id')
                     ->where('matches.status', 'Ended');
            })
            ->leftJoinSub($gameAchievements, 'game_stats', function ($join) {
                $join->on('game_stats.user_id', '=', 'users.id');
            })
            ->whereNull('users.deleted_at')
            ->where('users.type', '!=', 'A')
            ->groupBy('users.id', 'users.name', 'users.nickname', 'users.photo_avatar_filename')
            ->when($orderBy === 'bandeiras', function ($q) {
                $q->orderByDesc(DB::raw('COALESCE(game_stats.bandeiras, 0)'));
            })
            ->when($orderBy === 'capotes', function ($q) {
                $q->orderByDesc(DB::raw('COALESCE(game_stats.capotes, 0)'));
            })
            ->when($orderBy === 'wins', function ($q) {
                $q->orderByDesc('wins');
            })
            ->orderBy('last_win_at')
            ->orderBy('users.id')
            ->get();

        $mostWins = $winsRanking->skip($offset)->take($perPage)->values()->map(function ($user, $index) use ($offset) {
            $avatarUrl = $this->resolveAvatarUrl($user->photo_avatar_filename);
            return [
                'rank' => $offset + $index + 1,
                'id' => $user->id,
                'name' => $user->nickname ?? $user->name,
                'avatar_url' => $avatarUrl,
                'value' => (int) $user->wins,
                'bandeiras' => (int) ($user->bandeiras ?? 0),
                'capotes' => (int) ($user->capotes ?? 0),
            ];
        });

        $gameWinsRanking = User::select(
                'users.id',
                'users.name',
                'users.nickname',
                'users.photo_avatar_filename',
                DB::raw('COALESCE(game_stats.bandeiras, 0) as bandeiras'),
                DB::raw('COALESCE(game_stats.capotes, 0) as capotes')
            )
            ->selectRaw('COUNT(games.id) as wins, MAX(games.ended_at) as last_win_at')
            ->leftJoin('games', function ($join) {
                $join->on('users.id', '=', 'games.winner_user_id')
                     ->where('games.status', 'Ended');
            })
            ->leftJoinSub($gameAchievements, 'game_stats', function ($join) {
                $join->on('game_stats.user_id', '=', 'users.id');
            })
            ->whereNull('users.deleted_at')
            ->where('users.type', '!=', 'A')
            ->groupBy('users.id', 'users.name', 'users.nickname', 'users.photo_avatar_filename')
            ->when($orderBy === 'bandeiras', function ($q) {
                $q->orderByDesc(DB::raw('COALESCE(game_stats.bandeiras, 0)'));
            })
            ->when($orderBy === 'capotes', function ($q) {
                $q->orderByDesc(DB::raw('COALESCE(game_stats.capotes, 0)'));
            })
            ->when($orderBy === 'wins', function ($q) {
                $q->orderByDesc('wins');
            })
            ->orderBy('last_win_at')
            ->orderBy('users.id')
            ->get();

        $mostGameWins = $gameWinsRanking->skip($offset)->take($perPage)->values()->map(function ($user, $index) use ($offset) {
            $avatarUrl = $this->resolveAvatarUrl($user->photo_avatar_filename);
            return [
                'rank' => $offset + $index + 1,
                'id' => $user->id,
                'name' => $user->nickname ?? $user->name,
                'avatar_url' => $avatarUrl,
                'value' => (int) $user->wins,
                'bandeiras' => (int) ($user->bandeiras ?? 0),
                'capotes' => (int) ($user->capotes ?? 0),
            ];
        });

        $userWinsPosition = null;
        if ($currentUser) {
            $userWinsIndex = $winsRanking->search(fn($u) => $u->id === $currentUser->id);
            if ($userWinsIndex !== false) {
                $userWinsPosition = [
                    'rank' => $userWinsIndex + 1,
                    'id' => $currentUser->id,
                    'name' => $currentUser->nickname ?? $currentUser->name,
                    'avatar_url' => $this->resolveAvatarUrl($currentUser->photo_avatar_filename),
                    'value' => (int) $winsRanking[$userWinsIndex]->wins,
                    'bandeiras' => (int) ($winsRanking[$userWinsIndex]->bandeiras ?? 0),
                    'capotes' => (int) ($winsRanking[$userWinsIndex]->capotes ?? 0),
                    'total' => $totalUsers,
                ];
            }
        }

        $userGameWinsPosition = null;
        if ($currentUser) {
            $userGameWinsIndex = $gameWinsRanking->search(fn($u) => $u->id === $currentUser->id);
            if ($userGameWinsIndex !== false) {
                $userGameWinsPosition = [
                    'rank' => $userGameWinsIndex + 1,
                    'id' => $currentUser->id,
                    'name' => $currentUser->nickname ?? $currentUser->name,
                    'avatar_url' => $this->resolveAvatarUrl($currentUser->photo_avatar_filename),
                    'value' => (int) $gameWinsRanking[$userGameWinsIndex]->wins,
                    'bandeiras' => (int) ($gameWinsRanking[$userGameWinsIndex]->bandeiras ?? 0),
                    'capotes' => (int) ($gameWinsRanking[$userGameWinsIndex]->capotes ?? 0),
                    'total' => $totalUsers,
                ];
            }
        }

        $coinsRanking = User::select('id', 'name', 'nickname', 'photo_avatar_filename', 'coins_balance')
            ->whereNull('deleted_at')
            ->where('type', '!=', 'A')
            ->orderByDesc('coins_balance')
            ->orderBy('id')
            ->get();

        $mostCoins = $coinsRanking->skip($offset)->take($perPage)->values()->map(function ($user, $index) use ($offset) {
            $avatarUrl = $this->resolveAvatarUrl($user->photo_avatar_filename);
            return [
                'rank' => $offset + $index + 1,
                'id' => $user->id,
                'name' => $user->nickname ?? $user->name,
                'avatar_url' => $avatarUrl,
                'value' => (int) ($user->coins_balance ?? 0),
            ];
        });

        $userCoinsPosition = null;
        if ($currentUser) {
            $userCoinsIndex = $coinsRanking->search(fn($u) => $u->id === $currentUser->id);
            if ($userCoinsIndex !== false) {
                $userCoinsPosition = [
                    'rank' => $userCoinsIndex + 1,
                    'id' => $currentUser->id,
                    'name' => $currentUser->nickname ?? $currentUser->name,
                    'avatar_url' => $this->resolveAvatarUrl($currentUser->photo_avatar_filename),
                    'value' => (int) ($coinsRanking[$userCoinsIndex]->coins_balance ?? 0),
                    'total' => $totalUsers,
                ];
            }
        }

        $winsTotal = $winsRanking->count();
        $coinsTotal = $coinsRanking->count();
        $gameWinsTotal = $gameWinsRanking->count();

        return response()->json([
            'most_wins' => [
                'items' => $mostWins,
                'my_position' => $userWinsPosition,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $winsTotal,
                    'last_page' => (int) ceil($winsTotal / $perPage),
                    'has_more' => $page * $perPage < $winsTotal,
                ],
            ],
            'most_game_wins' => [
                'items' => $mostGameWins,
                'my_position' => $userGameWinsPosition,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $gameWinsTotal,
                    'last_page' => (int) ceil($gameWinsTotal / $perPage),
                    'has_more' => $page * $perPage < $gameWinsTotal,
                ],
            ],
            'most_coins' => [
                'items' => $mostCoins,
                'my_position' => $userCoinsPosition,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $coinsTotal,
                    'last_page' => (int) ceil($coinsTotal / $perPage),
                    'has_more' => $page * $perPage < $coinsTotal,
                ],
            ],
        ]);
    }
}
