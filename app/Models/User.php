<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ContactType;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Soluta\Subscription\Models\Concerns\HasSubscriptions;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasSubscriptions, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

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
            'user_type' => UserType::class,
            'status' => UserStatus::class,
        ];
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class)->latest();
    }

    public function userProducts()
    {
        return $this->belongsToMany(Product::class, ProductUser::class)
            ->withPivot('id', 'max_stock', 'current_stock', 'allow_production')
            ->as('info')
            ->withTimestamps();
    }

    public function socialAccounts()
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function defaultCustomer()
    {
        return $this->hasOne(Contact::class)->where('is_default', 1);
    }

    public function customers()
    {
        return $this->hasMany(Contact::class)
            ->whereIn('contact_type', [ContactType::BUYER, ContactType::BOTH]);
    }
}
