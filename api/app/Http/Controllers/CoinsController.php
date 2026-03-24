<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCoinPurchaseRequest;
use App\Models\Coin_Purchase;
use App\Models\Coin_Transaction;
use App\Models\Coin_Transaction_Type;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoinsController extends Controller
{
    public function purchase(StoreCoinPurchaseRequest $request, PaymentGatewayService $gateway)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();
        $euros = (int) $validated['value'];
        $coinsToCredit = $euros * 10; //1 euro = 10 coins

        //chamamos a gateway externa
        $result = $gateway->createDebit($validated['type'], $validated['reference'], $euros);
        if (!$result['ok']) {
            $status = $result['status'] ?? 422;
            $body = $result['body'] ?? ['message' => 'Payment gateway error'];
            return response()->json(['message' => 'Debit failed', 'details' => $body], $status);
        }

        //obter o tipo de transação de coin purchase
        $purchaseType = Coin_Transaction_Type::where('name', 'Coin purchase')->first();
        if (!$purchaseType) {
            return response()->json(['message' => 'Transaction type not configured'], 500);
        }

        $now = now();

        //inserir na BD a transação e a purchase
        return DB::transaction(function () use ($user, $euros, $coinsToCredit, $purchaseType, $validated, $now) {
            $transaction = Coin_Transaction::create([
                'transaction_datetime' => $now,
                'user_id' => $user->id,
                'coin_transaction_type_id' => $purchaseType->id,
                'coins' => $coinsToCredit,
            ]);

            Coin_Purchase::create([
                'purchase_datetime' => $now,
                'user_id' => $user->id,
                'coin_transaction_id' => $transaction->id,
                'euros' => $euros,
                'payment_type' => $validated['type'],
                'payment_reference' => $validated['reference'],
            ]);

            //atualizar saldo do user
            $user->coins_balance = ($user->coins_balance ?? 0) + $coinsToCredit;
            $user->save();

            return response()->json([
                'message' => 'Purchase successful completed',
                'coins_credited' => $coinsToCredit,
                'coins_balance' => (int) $user->coins_balance,
            ], 201);
        });
    }

    // obter saldo do user autenticado
    public function balance(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        return response()->json([
            'coins_balance' => (int) ($user->coins_balance ?? 0),
        ]);
    }

    // obter transações do user autenticado
    public function myTransactions(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $page = max(1, (int) $request->query('page', 1));

        $query = Coin_Transaction::with(['coin_transaction_type'])
            ->where('user_id', $user->id)
            ->orderByDesc('transaction_datetime');

        
        // filtros de tipo de transação (tipo id, exemplo: bonus, coins purchase, ...)
        if ($request->filled('type')) {
            $type = $request->query('type');
            if ($type) {
                $query->where('coin_transaction_type_id', $type);
            }
        }

        // filtros de tipo dp tipo de transação (C ou D - crédito ou débito)
        if ($request->filled('type_code')) {
            $typeCode = $request->query('type_code');
            if ($typeCode) {
                $query->whereHas('coin_transaction_type', function ($q) use ($typeCode) {
                    $q->where('type', $typeCode);
                });
            }
        }

        //filtro por data
        if ($request->filled('began_after')) {
            $query->whereDate('transaction_datetime', '>=', $request->query('began_after'));
        }

        if ($request->filled('ended_before')) {
            $query->whereDate('transaction_datetime', '<=', $request->query('ended_before'));
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'datetime' => $t->transaction_datetime,
                'type' => $t->coin_transaction_type?->name,
                'type_code' => $t->coin_transaction_type?->type,
                'type_id' => $t->coin_transaction_type?->id,
                'coins' => (int) $t->coins,
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    // obter todas as transações de coins (só admins)
    public function allTransactions(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        if (($user->type ?? 'P') !== 'A') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $page = max(1, (int) $request->query('page', 1));

        $query = Coin_Transaction::with(['coin_transaction_type', 'user'])
            ->orderByDesc('transaction_datetime');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('user_name')) {
            $name = $request->query('user_name');
            $query->whereHas('user', function ($q) use ($name) {
                $q->where('name', 'like', "%$name%")
                  ->orWhere('nickname', 'like', "%$name%")
                  ->orWhere('email', 'like', "%$name%");
            });
        }

        // filtros de tipo de transação (tipo id, exemplo: bonus, coins purchase, ...)
        if ($request->filled('type')) {
            $type = $request->query('type');
            if ($type) {
                $query->where('coin_transaction_type_id', $type);
            }
        }

        // filtros de tipo dp tipo de transação (C ou D - crédito ou débito)
        if ($request->filled('type_code')) {
            $typeCode = $request->query('type_code');
            if ($typeCode) {
                $query->whereHas('coin_transaction_type', function ($q) use ($typeCode) {
                    $q->where('type', $typeCode);
                });
            }
        }

        //filtro por data
        if ($request->filled('began_after')) {
            $query->whereDate('transaction_datetime', '>=', $request->query('began_after'));
        }
        if ($request->filled('ended_before')) {
            $query->whereDate('transaction_datetime', '<=', $request->query('ended_before'));
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($t) {
            return [
                'id' => $t->id,
                'datetime' => $t->transaction_datetime,
                'user' => [
                    'id' => $t->user?->id,
                    'nickname' => $t->user?->nickname,
                    'name' => $t->user?->name,
                    'email' => $t->user?->email,
                ],
                'type' => $t->coin_transaction_type?->name,
                'type_code' => $t->coin_transaction_type?->type,
                'type_id' => $t->coin_transaction_type?->id,
                'coins' => (int) $t->coins,
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    // obter tipos de transactions de moedas (bonus, coins purchase...)
    public function transactionTypes()
    {
        $types = Coin_Transaction_Type::all(['id', 'name', 'type']);
        return response()->json($types);
    }
}
