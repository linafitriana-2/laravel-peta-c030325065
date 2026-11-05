<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // <-- Tambahkan use ini di bagian atas

class User extends Authenticatable
{

    use HasFactory, Notifiable;

    
    public const ROLE_ADMIN = 'admin';
    public const ROLE_DINAS = 'dinas';
    public const ROLE_SEKOLAH = 'sekolah';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',       
        'sekolah_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDinas(): bool
    {
        return $this->role === self::ROLE_DINAS;
    }

    public function isSekolah(): bool
    {
        return $this->role === self::ROLE_SEKOLAH;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_DINAS => 'Dinas Pendidikan',
            default => 'Sekolah',
        };
    }
}