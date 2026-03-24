<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCustomization extends Model
{
    protected $table = 'user_customizations';

    protected $fillable = [
        'user_id',
        'customization_id',
        'purchased_at',
        'meta',
    ];

    public $timestamps = true;

    protected $casts = [
        'purchased_at' => 'datetime',
        'meta' => 'array',
    ];
}