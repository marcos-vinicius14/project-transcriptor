<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transcription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        "user_id",
        "original_file_path",
        "status",
    ];

    protected $casts = [
        "status" => "string",
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
