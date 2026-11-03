<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class LkhResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $parsedDate = $this->tanggal ? Carbon::parse($this->tanggal) : null;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'tanggal' => $parsedDate ? $parsedDate->format('Y-m-d') : null,
            'tanggal_formatted' => $parsedDate ? $parsedDate->locale('id')->translatedFormat('l, d F Y') : null,
            'kegiatan' => $this->kegiatan,
            'output' => $this->output,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
