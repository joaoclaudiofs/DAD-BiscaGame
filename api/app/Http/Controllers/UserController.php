<?php

namespace App\Http\Controllers;

use App\Models\Customization;
use App\Models\User;
use App\Models\UserCustomization;
use App\Models\Coin_Transaction;
use App\Models\Coin_Transaction_Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Game;
use App\Models\Matches;
use Illuminate\Http\Request;

class UserController extends Controller
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
            $candidates[] = 'photos_avatars/' . $filename;
            $candidates[] = 'photos_avatars/' . $filename . '.jpeg';
            $candidates[] = 'photos_avatars/' . $filename . '.jpg';
            $candidates[] = 'photos_avatars/' . $filename . '.png';
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

    public function index(Request $request)
    {
        $user = $request->user();

        //apenas os admins podem ver a lista de users
        if (!$user || $user->type !== 'A') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $users = User::whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return $users;
    }

    public function store(Request $request)
    {
        $user = User::create($request->all());
        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return $user;
    }

    public function update(Request $request, User $user)
    {
        $user->update($request->all());
        return response()->json($user);
    }

    public function destroy(string $id)
    {
        //
    }

    public function addCoins(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'in:Bonus,Game payout,Match payout'],
        ]);

        $transactionType = Coin_Transaction_Type::where('name', $validated['type'])->first();
        if (!$transactionType) {
            return response()->json(['message' => 'Transaction type not configured'], 500);
        }

        return DB::transaction(function () use ($user, $validated, $transactionType) {
            $now = now();
            $coins = (int) $validated['amount'];

            Coin_Transaction::create([
                'transaction_datetime' => $now,
                'user_id' => $user->id,
                'coin_transaction_type_id' => $transactionType->id,
                'coins' => $coins,
            ]);

            $user->coins_balance = ($user->coins_balance ?? 0) + $coins;
            $user->save();

            return response()->json([
                'message' => 'Coins added successfully.',
                'coins_balance' => (int) $user->coins_balance,
            ]);
        });
    }

    public function removeCoins(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'in:Game fee,Match stake'],
        ]);

        $transactionType = Coin_Transaction_Type::where('name', $validated['type'])->first();
        if (!$transactionType) {
            return response()->json(['message' => 'Transaction type not configured'], 500);
        }

        $current = $user->coins_balance ?? 0;

        if ($validated['amount'] > $current) {
            return response()->json([
                'message' => 'Insufficient coins.',
                'current_coins' => $current,
            ], 422);
        }

        return DB::transaction(function () use ($user, $validated, $transactionType, $current) {
            $now = now();
            $coins = (int) $validated['amount'];

            Coin_Transaction::create([
                'transaction_datetime' => $now,
                'user_id' => $user->id,
                'coin_transaction_type_id' => $transactionType->id,
                'coins' => -$coins,
            ]);

            $user->coins_balance = $current - $coins;
            $user->save();

            return response()->json([
                'message' => 'Coins removed successfully.',
                'coins_balance' => (int) $user->coins_balance,
            ]);
        });
    }

    public function purchaseCustomization(Request $request, Customization $customization)
    {

        $user = $request->user();

        if (
            DB::table('user_customizations')->where('user_id', $user->id)->where('customization_id', $customization->id)->exists() ||
            ($customization->price == 0)
        ) {
            return response()->json(['message' => 'Already owned'], 409);
        }

        if (($user->coins_balance ?? 0) < $customization->price) {
            return response()->json(['message' => 'Insufficient coins'], 422);
        }

        $user->coins_balance -= $customization->price;
        $user->save();

        DB::table('user_customizations')->insert([
            'user_id' => $user->id,
            'customization_id' => $customization->id,
            'purchased_at' => now(),
        ]);

        return response()->json(['message' => 'Purchased', 'coins_balance' => $user->coins_balance]);
    }

    public function equipCustomization(Request $request, Customization $customization)
    {

        $user = $request->user();

        $isOwner = UserCustomization::where('user_id', $user->id)
            ->where('customization_id', $customization->id)
            ->exists()
            || $customization->price == 0;

        if (!$isOwner) {
            return response()->json(['message' => 'Not owned'], 409);
        }

        $type = $customization->type;

        if ($type === 'avatar') {
            $user->current_avatar_customization_id = $customization->id;
        } else if ($type === 'deck') {
            $user->current_deck_customization_id = $customization->id;
        } else {
            return response()->json(['message' => 'Invalid type'], 400);
        }

        $user->save();

        return response()->json(['message' => 'Equipped']);
    }

    public function myStats(Request $request)
    {
        $user = $request->user();
        return $this->buildUserStats($user);
    }

    public function userStats(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        return $this->buildUserStats($user);
    }

    private function buildUserStats($user)
    {
        //todos os matches onde o user participou
        $matchesQuery = Matches::query()
            ->where('status', 'Ended');

        $wins = (clone $matchesQuery)
            ->where('winner_user_id', $user->id)
            ->count();

        $losses = (clone $matchesQuery)
            ->where('loser_user_id', $user->id)
            ->count();

        //empates (onde não há vencedor e o status é 'Ended')
        $draws = Matches::where('status', 'Ended')
            ->whereNull('winner_user_id')
            ->where(function ($q) use ($user) {
                $q->where('player1_user_id', $user->id)
                  ->orWhere('player2_user_id', $user->id);
            })
            ->count();

        //interrupted 
        $interrupted = Matches::where('status', 'Interrupted')
            ->where(function ($q) use ($user) {
                $q->where('player1_user_id', $user->id)
                  ->orWhere('player2_user_id', $user->id);
            })
            ->count();

        $totalMatches = $wins + $losses + $draws;
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;

        $pointsAsPlayer1 = Matches::where('player1_user_id', $user->id)->sum('player1_points');
        $pointsAsPlayer2 = Matches::where('player2_user_id', $user->id)->sum('player2_points');
        $totalPoints = $pointsAsPlayer1 + $pointsAsPlayer2;

        $avgPoints = $totalMatches > 0 ? round($totalPoints / $totalMatches, 1) : 0;

        //vitórias e derrotas por variante
        $matchWinsByVariant = (clone $matchesQuery)
            ->where('winner_user_id', $user->id)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $matchLossesByVariant = (clone $matchesQuery)
            ->where('loser_user_id', $user->id)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        //partidas ended 
        $gamesQuery = Game::query()
            ->where('status', 'Ended');

        $gameWinsTotal = (clone $gamesQuery)
            ->where('winner_user_id', $user->id)
            ->count();

        $gameWinsByVariant = (clone $gamesQuery)
            ->where('winner_user_id', $user->id)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        //capote
        $capoteGamesQuery = (clone $gamesQuery)
            ->where('winner_user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw('(winner_user_id = player1_user_id AND player1_points >= 91 AND player1_points < 120)')
                    ->orWhereRaw('(winner_user_id = player2_user_id AND player2_points >= 91 AND player2_points < 120)');
            });

        $totalCapotes = (clone $capoteGamesQuery)->count();
        $capotesByVariant = (clone $capoteGamesQuery)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        //bandeira
        $bandeiraGamesQuery = (clone $gamesQuery)
            ->where('winner_user_id', $user->id)
            ->where(function ($q) {
                $q->whereRaw('(winner_user_id = player1_user_id AND player1_points = 120)')
                    ->orWhereRaw('(winner_user_id = player2_user_id AND player2_points = 120)');
            });

        $totalBandeiras = (clone $bandeiraGamesQuery)->count();
        $bandeirasByVariant = (clone $bandeiraGamesQuery)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        //últimas 10 matches
        $recentMatches = Matches::where(function ($q) use ($user) {
            $q->where('player1_user_id', $user->id)
                ->orWhere('player2_user_id', $user->id);
        })
            ->with(['player1:id,name,nickname', 'player2:id,name,nickname'])
            ->where('status', 'Ended')
            ->orderBy('ended_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($match) use ($user) {
                $isP1 = $match->player1_user_id === $user->id;
                $userPoints = $isP1 ? $match->player1_points : $match->player2_points;
                $opponentPoints = $isP1 ? $match->player2_points : $match->player1_points;
                $opponent = $isP1 ? $match->player2 : $match->player1;

                $winnerId = $match->winner_user_id;
                $result = null;
                if ($user) {
                    $result = ($winnerId == $user->id) ? 'win' : 'loss';
                }

                return [
                    'id' => $match->id,
                    'result' => $result,
                    'user_points' => $userPoints,
                    'opponent_points' => $opponentPoints,
                    'opponent_name' => $opponent?->nickname ?? $opponent?->name ?? 'Bot',
                    'played_at' => $match->ended_at,
                    'duration' => $match->total_time,
                ];
            });

        return response()->json([
            'wins' => $wins,
            'losses' => $losses,
            'total_matches' => $totalMatches,
            'win_rate' => $winRate,
            'total_points' => $totalPoints,
            'avg_points' => $avgPoints,
            'match_stats' => [
                'wins' => $wins,
                'losses' => $losses,
                'draws' => $draws,
                'interrupted' => $interrupted,
                'total' => $totalMatches,
                'win_rate' => $winRate,
                'points' => [
                    'total' => $totalPoints,
                    'average' => $avgPoints,
                ],
                'wins_by_variant' => $matchWinsByVariant,
                'losses_by_variant' => $matchLossesByVariant,
            ],
            'game_stats' => [
                'wins' => $gameWinsTotal,
                'wins_by_variant' => $gameWinsByVariant,
                'capotes' => $totalCapotes,
                'capotes_by_variant' => $capotesByVariant,
                'bandeiras' => $totalBandeiras,
                'bandeiras_by_variant' => $bandeirasByVariant,
            ],
            'recent_matches' => $recentMatches,
        ]);
    }

    // public function getEquippedDeck(Request $request)
    // {
    //     $user = $request->user();

    //     if (!$user->current_deck_customization_id) {
    //         //deck padrão
    //         $deck = Customization::where('type', 'deck')->where('price', 0)->first();
    //     } else {
    //         $deck = Customization::find($user->current_deck_customization_id);
    //     }

    //     return response()->json($deck);
    // }

    // public function getEquippedAvatar(Request $request)
    // {
    //     $user = $request->user();

    //     // If user has uploaded a custom photo, return that
    //     if ($user->photo_avatar_filename) {
    //         return response()->json([
    //             'type' => 'user_photo',
    //             'image_url' => asset('storage/photos_avatars/' . $user->photo_avatar_filename),
    //             'filename' => $user->photo_avatar_filename,
    //         ]);
    //     } else {
    //         return response()->json([
    //             'type' => 'user_photo',
    //             'image_url' => asset('storage/photos_avatars/anonymous.png'),
    //             'filename' => 'anonymous.png',
    //         ]);
    //     }

    //     return response()->json($avatar);
    // }

    // public function getOwnedCustomizations(Request $request)
    // {
    //     $user = $request->user();

    //     $ownedCustomizations = UserCustomization::where('user_id', $user->id)
    //         ->pluck('customization_id')
    //         ->toArray();

    //     return response()->json($ownedCustomizations);
    // }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'nickname' => ['sometimes', 'required', 'string', 'max:255', 'unique:users,nickname,' . $user->id],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'photo_avatar_filename' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo_avatar_filename')) {
            $file = $request->file('photo_avatar_filename');
            $extension = $file->getClientOriginalExtension();

            // Generate numbered filename
            $directory = storage_path('app/public/photos_avatars');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $number = 1;
            do {
                $filename = sprintf('%06d', $number) . '.' . $extension;
                $number++;
            } while (file_exists($directory . '/' . $filename));

            $file->storeAs('photos_avatars', $filename, 'public');
            $validated['photo_avatar_filename'] = $filename;
        }

        $user->update($validated);
        $user->refresh();

        return response()->json($user);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:3', 'confirmed'],
        ]);

        // Verify current password
        if (!\Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        //os admins não podem apagar as próprias contas
        if ($user->type === 'A') {
            return response()->json([
                'message' => 'Administrators cannot delete their own accounts',
            ], 403);
        }

        $validated = $request->validate([
            'nickname' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        //verifica nickname
        if ($validated['nickname'] !== $user->nickname) {
            return response()->json([
                'message' => 'Nickname does not match',
            ], 422);
        }

        //verifica password
        if (!\Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect',
            ], 422);
        }

        //soft delete
        $user->delete();

        //remove tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
