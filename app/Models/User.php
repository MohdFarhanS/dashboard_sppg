<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    const ROLE_SUPERADMIN = 'superadmin';

    const ROLE_KETUA_SPPG = 'ketua_sppg';

    const ROLE_AHLI_GIZI = 'ahli_gizi';

    const ROLE_AKUNTAN = 'akuntan';

    protected $fillable = [
        'name',
        'nama_lengkap',
        'email',
        'password',
        'role',
        'unit_sppg',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Relasi ────────────────────────────────────────────────────────────────

    public function menuHarians(): HasMany
    {
        return $this->hasMany(MenuHarian::class);
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    // ─── Role helpers ──────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isKetuaSppg(): bool
    {
        return $this->role === self::ROLE_KETUA_SPPG;
    }

    public function isAhliGizi(): bool
    {
        return $this->role === self::ROLE_AHLI_GIZI;
    }

    public function isAkuntan(): bool
    {
        return $this->role === self::ROLE_AKUNTAN;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /** Apakah termasuk salah satu role operasional (bukan superadmin) */
    public function isOperational(): bool
    {
        return $this->hasAnyRole([self::ROLE_KETUA_SPPG, self::ROLE_AHLI_GIZI, self::ROLE_AKUNTAN]);
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_KETUA_SPPG => 'Ketua SPPG',
            self::ROLE_AHLI_GIZI => 'Ahli Gizi',
            self::ROLE_AKUNTAN => 'Akuntan',
            default => $role,
        };
    }

    public static function allRoles(): array
    {
        return [
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_KETUA_SPPG => 'Ketua SPPG',
            self::ROLE_AHLI_GIZI => 'Ahli Gizi',
            self::ROLE_AKUNTAN => 'Akuntan',
        ];
    }
}
