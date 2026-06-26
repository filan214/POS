<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
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

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'cashier_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    /**
     * The user's primary role name — convenience for views/labels that read
     * `$user->role`. Backed by spatie roles, not a column.
     */
    public function getRoleAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    /** Initials for the avatar chip, e.g. "Dewi Lestari" → "DL". */
    public function getInitialsAttribute(): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim((string) $this->name))));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0] ?? '', 1, 1);

        return strtoupper($first.$second);
    }

    /** The cashier's currently-open shift, if any. */
    public function openShift(): ?Shift
    {
        return $this->shifts()->where('status', 'open')->latest('opened_at')->first();
    }
}
