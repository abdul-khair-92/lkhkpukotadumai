<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LkhHoliday extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['id', 'tanggal', 'keterangan'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    protected $table = 'lkh_holidays';
}
