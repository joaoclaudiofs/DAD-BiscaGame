<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coin_Transaction_Type extends Model
{
    use SoftDeletes;
    protected $table = 'coin_transaction_types';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'deleted_at',
    ];

    public function coin_transactions()
    {
        return $this->hasMany(Coin_Transaction::class, 'coin_transaction_type_id', 'id');
    }
}

?>
