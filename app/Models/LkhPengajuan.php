<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LkhPengajuan extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REVISION = 'revision';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'lkh_pengajuans';

    protected $fillable = [
        'user_id',
        'bulan',
        'tahun',
        'status',
        'catatan_atasan',
        'reviewed_by',
        'reviewed_at',
        'pdf_path',
        'document_token',
        'qr_pengaju_token',
        'qr_penyetuju_token',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'reviewed_at' => 'datetime',
        'user_id' => 'string',
        'reviewed_by' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function notification(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
