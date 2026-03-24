<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{

    public $timestamps = false;

    protected $fillable = [
        'type',
        'player1_user_id',
        'player2_user_id',
        'is_draw',
        'winner_user_id',
        'loser_user_id',
        'match_id',
        'status',
        'began_at',
        'ended_at',
        'total_time',
        'player1_points',
        'player2_points',
    ];

    public function winner()
    {
        return $this->belongsTo(User::class, "winner_user_id", "id")->withTrashed();
    }

    public function loser()
    {
        return $this->belongsTo(User::class, "loser_user_id", "id")->withTrashed();
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_user_id', 'id')->withTrashed();
    }
    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_user_id', 'id')->withTrashed();
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(Matches::class, 'match_id', 'id');
    }

    public function coinTransactions()
    {
        return $this->hasMany(Coin_Transaction::class);
    }

}
