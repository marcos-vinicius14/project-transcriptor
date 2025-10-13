<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'original_file_name' => basename($this->original_file_path),
            'created_at' => $this->created_at->toDateTimeString(),
            'links' => [
                'self' => route('transcriptions.show', ['transcription' => $this->id]), 
            ],
        ];
    }
}
