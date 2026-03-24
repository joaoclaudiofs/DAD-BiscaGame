<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'nickname',
        'blocked',
        'photo_avatar_filename',
        'coins_balance',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Append custom attributes to the model's array form.
     *
     * @var array
     */
    protected $appends = ['photo_url'];

    /**
     * Get the user's photo URL.
     *
     * @return string
     */
    public function getPhotoUrlAttribute()
    {
        $filename = $this->photo_avatar_filename ?: 'anonymous.png';
        return asset('storage/photos_avatars/' . $filename);
    }

    public function ownedCustomizations()
    {
        return $this->belongsToMany(Customization::class, 'user_customizations')
            ->withTimestamps()
            ->withPivot('purchased_at', 'meta');
    }

    public function currentAvatar()
    {
        return $this->belongsTo(Customization::class, 'current_avatar_customization_id');
    }

    public function currentDeck()
    {
        return $this->belongsTo(Customization::class, 'current_deck_customization_id');
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function matches()
    {
        return $this->hasMany(Matches::class);
    }

    public function coinPurchases()
    {
        return $this->hasMany(Coin_Purchase::class);
    }

    public function coinTransactions()
    {
        return $this->hasMany(Coin_Transaction::class);
    }
}
