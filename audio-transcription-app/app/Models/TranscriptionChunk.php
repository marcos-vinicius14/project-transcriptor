<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranscriptionChunk extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['transcription_id', 'chunk_path', 'content'];

    public function transcription(): BelongsTo
    {
        return $this->belongsTo(Transcription::class);
    }
}