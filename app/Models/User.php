<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const LEVEL_SMP = 'smp';
    public const LEVEL_SMA_SMK = 'sma_smk';
    public const LEVEL_MHS = 'mhs';

    protected $fillable = ['name', 'username', 'email', 'phone', 'password', 'level'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => 'user', 'is_active' => true];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function testSessions(): HasMany
    {
        return $this->hasMany(TestSession::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function developmentChecks(): HasMany
    {
        return $this->hasMany(DevelopmentCheck::class);
    }

    /** Admin penuh (admin/owner): data hasil, akun, akreditasi, aturan. */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }

    /** Masuk dengan kata sandi sementara dari admin (aman walau atribut belum dimuat, mis. model baru). */
    public function mustChangePassword(): bool
    {
        return (bool) ($this->attributes['must_change_password'] ?? false);
    }

    /** Boleh masuk panel admin: admin/owner, atau editor konten (guru BK) dengan akses terbatas. */
    public function canEnterAdmin(): bool
    {
        return in_array($this->role, ['editor', 'admin', 'owner'], true);
    }

    public function isCollegeStudent(): bool
    {
        return $this->level === self::LEVEL_MHS;
    }

    public function needsProgramOnboarding(): bool
    {
        return $this->isCollegeStudent() && (! $this->profile?->current_program_offering_id || $this->profile->feels_mismatch === null);
    }

    /** Hasil tes terakhir yang sudah selesai, atau null. */
    public function latestResult(): ?TestResult
    {
        return TestResult::query()
            ->whereHas('session', fn ($q) => $q->where('user_id', $this->id)->where('status', 'completed'))
            ->latest('id')
            ->first();
    }

    /** Ubah nomor HP Indonesia ke bentuk 08xxxxxxxx. */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
