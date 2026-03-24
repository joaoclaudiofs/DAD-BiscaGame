<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Game;
use App\Models\Matches;
use App\Models\Coin_Purchase;
use App\Models\Coin_Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function public(Request $request)
    {
        //total de jogadores (excluindo admins e eliminados)
        $totalPlayers = User::where('type', '!=', 'A')
            ->whereNull('deleted_at')
            ->count();

        //total de jogos jogados
        $totalGames = Game::where('status', 'Ended')->count();

        //total de partidas jogadas
        $totalMatches = Matches::where('status', 'Ended')->count();

        //jogos por variante
        $gamesByVariant = Game::where('status', 'Ended')
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($item) => [$item->type => $item->total]);

        //atividade recente (últimos 7 dias)
        $sevenDaysAgo = now()->subDays(7);
        $recentGames = Game::where('status', 'Ended')
            ->where('ended_at', '>=', $sevenDaysAgo)
            ->count();

        $recentMatches = Matches::where('status', 'Ended')
            ->where('ended_at', '>=', $sevenDaysAgo)
            ->count();

        //media de duração dos jogos (em segundos)
        $avgGameDuration = Game::where('status', 'Ended')
            ->whereNotNull('total_time')
            ->avg('total_time');

        //media de duração das partidas (em segundos)
        $avgMatchDuration = Matches::where('status', 'Ended')
            ->whereNotNull('total_time')
            ->avg('total_time');

        //jogos por dia (últimos 30 dias)
        $gamesPerDay = Game::where('status', 'Ended')
            ->where('ended_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(ended_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'total_players' => $totalPlayers,
            'total_games' => $totalGames,
            'total_matches' => $totalMatches,
            'games_by_variant' => $gamesByVariant,
            'recent_activity' => [
                'games_last_7_days' => $recentGames,
                'matches_last_7_days' => $recentMatches,
            ],
            'averages' => [
                'game_duration' => round($avgGameDuration ?? 0, 2),
                'match_duration' => round($avgMatchDuration ?? 0, 2),
            ],
            'games_per_day' => $gamesPerDay,
        ]);
    }

    public function admin(Request $request)
    {
        $user = $request->user();

        //verificar se é admin
        if (!$user || $user->type !== 'A') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        //estatisticas publicas
        $publicStats = $this->public($request)->getData();

        //estatisticas de users
        $totalUsers = User::count();
        $totalAdmins = User::where('type', 'A')->count();
        $totalPlayers = User::where('type', '!=', 'A')->whereNull('deleted_at')->count();
        $blockedUsers = User::where('blocked', true)->count();
        $deletedUsers = User::whereNotNull('deleted_at')->count();

        //registos de users ao longo do tempo (últimos 30 dias)
        $userRegistrationsPerDay = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        //estatísticas de compras de moedas dentro do período (default últimos 30 dias)
        $purchasesFrom = $request->input('purchases_from');
        $purchasesTo = $request->input('purchases_to');

        $startDate = $purchasesFrom ? $purchasesFrom : now()->subDays(30)->toDateString();
        $endDate = $purchasesTo ? $purchasesTo : now()->toDateString();

        $totalCoinsPurchased = Coin_Purchase::join('coin_transactions', 'coin_purchases.coin_transaction_id', '=', 'coin_transactions.id')
            ->whereDate('purchase_datetime', '>=', $startDate)
            ->whereDate('purchase_datetime', '<=', $endDate)
            ->sum('coin_transactions.coins');

        $totalPurchaseRevenue = Coin_Purchase::whereDate('purchase_datetime', '>=', $startDate)
            ->whereDate('purchase_datetime', '<=', $endDate)
            ->sum('euros');

        $totalPurchases = Coin_Purchase::whereDate('purchase_datetime', '>=', $startDate)
            ->whereDate('purchase_datetime', '<=', $endDate)
            ->count();

        $purchasesPerDay = Coin_Purchase::select(
            DB::raw('DATE(purchase_datetime) as date'),
            DB::raw('count(*) as count'),
            DB::raw('sum(euros) as revenue')
        )
            ->whereDate('purchase_datetime', '>=', $startDate)
            ->whereDate('purchase_datetime', '<=', $endDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        //top 10 players que mais compraram moedas
        $purchasesByPlayer = Coin_Purchase::select(
            'users.id',
            'users.name',
            'users.nickname',
            DB::raw('count(*) as purchase_count'),
            DB::raw('sum(coin_transactions.coins) as total_coins'),
            DB::raw('sum(coin_purchases.euros) as total_spent')
        )
            ->join('users', 'coin_purchases.user_id', '=', 'users.id')
            ->join('coin_transactions', 'coin_purchases.coin_transaction_id', '=', 'coin_transactions.id')
            ->groupBy('users.id', 'users.name', 'users.nickname')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        //transações de moedas por tipo
        $coinTransactions = Coin_Transaction::join('coin_transaction_types', 'coin_transactions.coin_transaction_type_id', '=', 'coin_transaction_types.id')
            ->select(
                DB::raw('COALESCE(coin_transaction_types.type, coin_transaction_types.name) as transaction_type'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(coin_transactions.coins) as total_coins')
            )
            ->groupBy('coin_transaction_types.type', 'coin_transaction_types.name')
            ->get();

        //partidas por dia dentro do período (default últimos 30 dias)
        $matchesFrom = $request->input('matches_from');
        $matchesTo = $request->input('matches_to');

        $matchesStartDate = $matchesFrom ? $matchesFrom : now()->subDays(30)->toDateString();
        $matchesEndDate = $matchesTo ? $matchesTo : now()->toDateString();

        $matchesPerDay = Matches::select(
            DB::raw('DATE(ended_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('status', 'Ended')
            ->whereDate('ended_at', '>=', $matchesStartDate)
            ->whereDate('ended_at', '<=', $matchesEndDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        //jogos por estado
        $gamesByStatus = Game::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => $item->count]);

        //jogadores ativos nos últimos 7 dias
        $activePlayers = User::whereIn('id', function ($query) {
            $query->select('player1_user_id')
                ->from('games')
                ->where('ended_at', '>=', now()->subDays(7))
                ->union(
                    DB::table('games')
                        ->select('player2_user_id')
                        ->where('ended_at', '>=', now()->subDays(7))
                );
        })->count();

        return response()->json([
            'public' => $publicStats,
            'users' => [
                'total' => $totalUsers,
                'admins' => $totalAdmins,
                'players' => $totalPlayers,
                'blocked' => $blockedUsers,
                'deleted' => $deletedUsers,
                'active_last_7_days' => $activePlayers,
                'registrations_per_day' => $userRegistrationsPerDay,
            ],
            'coins' => [
                'total_purchased' => $totalCoinsPurchased,
                'total_revenue' => $totalPurchaseRevenue,
                'total_purchases' => $totalPurchases,
                'purchases_per_day' => $purchasesPerDay,
                'purchases_by_player' => $purchasesByPlayer,
                'transactions_by_type' => $coinTransactions,
            ],
            'matches_per_day' => $matchesPerDay,
            'games_by_status' => $gamesByStatus,
        ]);
    }
}
