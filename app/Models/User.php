<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\Sanctum;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, softDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'nip', 'jabatan', 'subbagian', 'atasan_id', 'email', 'password', 'email_verified_at', 'level_id', 'access_group_id',
    ];

    protected $hidden = [
        'password', 'remember_token', 'access_group_id', 'level_id', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at', 'first_name', 'last_name',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'id' => 'string',
    ];

    protected $appends = ['name'];

    protected $primaryKey = 'id';

    public function level(): object
    {
        return $this->belongsTo(Level::class);
    }

    public function access_group(): object
    {
        return $this->belongsTo(AccessGroup::class);
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function bawahan(): HasMany
    {
        return $this->hasMany(User::class, 'atasan_id');
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = $value ? bcrypt($value) : $this->password;
    }

    public function tokens(): object
    {
        return $this->morphMany(Sanctum::$personalAccessTokenModel, 'tokenable');
    }

    public function log(): object
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getCreateAttribute(): bool
    {
        return $this->access_group->canAccess('create');
    }

    public function getReadAttribute(): bool
    {
        return $this->access_group->canAccess('read');
    }

    public function getUpdateAttribute(): bool
    {
        return $this->access_group->canAccess('update');
    }

    public function getDeleteAttribute(): bool
    {
        return $this->access_group->canAccess('delete');
    }

    /**
     * Akses modul Pengajuan LKH (menu & rute): root/admin, atasan berdasarkan jabatan,
     * atau user yang punya minimal satu bawahan langsung.
     *
     * Jabatan 1–2 mengikuti filter atasan di UserController (Sekretaris, Kepala Subbagian).
     */
    public function canAccessLkhPengajuan(): bool
    {
        $this->loadMissing('access_group');

        if (in_array($this->access_group?->code ?? '', ['root', 'admin'], true)) {
            return true;
        }

        if ($this->bawahan()->exists()) {
            return true;
        }

        return in_array((int) $this->jabatan, [1, 2], true);
    }

    /**
     * Akses modul Monitoring LKH:
     * - root/admin boleh akses
     * - Sekretaris (jabatan 1) dapat memantau seluruh pegawai
     * - Kepala Subbagian (jabatan 2) memantau pegawai pada subbagiannya
     */
    public function canAccessLkhMonitoring(): bool
    {
        $this->loadMissing('access_group');

        if (in_array($this->access_group?->code ?? '', ['root', 'admin'], true)) {
            return true;
        }

        return in_array((int) $this->jabatan, [1, 2], true);
    }

    public function scopeFilterLevel($query)
    {
        $level = auth()->user()->level->code;
        if ($level != 'root') {
            if ($level == 'user') {
                $query->where('id', auth()->id());
            }
            $query->where('level_id', '!=', '1');
        }

        return $query;
    }

    public function getAllUserIdAttribute(): array
    {
        return $this->whereNotIn('level_id', [1])->pluck('id')->toArray();
    }
}
