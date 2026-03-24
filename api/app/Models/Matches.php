<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    protected $table = 'matches';

    public $timestamps = false;

    protected $fillable = [
        'type', 
        'player1_user_id', 
        'player2_user_id', 
        'winner_user_id',
        'loser_user_id',
        'status', 
        'stake',
        'began_at', 
        'ended_at', 
        'total_time', 
        'player1_marks', 
        'player2_marks', 
        'player1_points',
        'player2_points',
    ];

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_user_id', 'id')->withTrashed();
    }
    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_user_id', 'id')->withTrashed();
    }
    public function games()
    {
        return $this->hasMany(Game::class, 'match_id', 'id');
    }
    public function coinTransactions()
    {
        return $this->hasMany(Coin_Transaction::class, 'match_id', 'id');
    }
}



?>