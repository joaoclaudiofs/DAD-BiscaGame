<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coin_Transaction extends Model
{
    protected $table = 'coin_transactions'; 
    public $timestamps = false;

    protected $fillable = [
        'transaction_datetime',
        'user_id',
        'match_id',
        'game_id',
        'coin_transaction_type_id',
        'coins',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
    public function match()
    {
        return $this->belongsTo(Matches::class, 'match_id', 'id');
    }
    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id', 'id');
    }
    public function coin_transaction_type()
    {
        return $this->belongsTo(Coin_Transaction_Type::class, 'coin_transaction_type_id', 'id')->withTrashed();
    }
    public function coin_purchase()
    {
        return $this->hasOne(Coin_Purchase::class, 'coin_transaction_id', 'id');
    }

}

?>