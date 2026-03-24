<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coin_Purchase extends Model
{
    public $timestamps = false;
    protected $table = 'coin_purchases';
    protected $fillable = [
        'purchase_datetime',
        'user_id',
        'coin_transaction_id',
        'euros',
        'payment_type',
        'payment_reference',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }


    public function coin_transaction()
    {
        return $this->belongsTo(Coin_Transaction::class, 'coin_transaction_id', 'id');
    }

}
?>