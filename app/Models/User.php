<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use App\Models\Appointment; // opzionale: se preferisci l'import

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profilo',
        'table_preferences',
        // opzionale: se vuoi poter fare fill() anche dei token
        // 'dropbox_refresh_token',
        // 'dropbox_access_token',
        // 'dropbox_token_expires_at',
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

    // Relazione corretta (importa Appointment oppure usa FQCN)
    public function appointments()
    {
        return $this->hasMany(\App\Models\Appointment::class);
        // oppure: return $this->hasMany(Appointment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'table_preferences'        => 'array',

            // 🔐 Cast fondamentali per Dropbox
            'dropbox_refresh_token'    => 'encrypted',
            'dropbox_token_expires_at' => 'datetime',
        ];
    }
}
