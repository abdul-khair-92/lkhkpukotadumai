<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LKH extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['id', 'user_id', 'tanggal', 'kegiatan', 'output'];

    protected $casts = [
        'user_id' => 'string',
    ];

    protected $table = 'l_k_h_s';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
