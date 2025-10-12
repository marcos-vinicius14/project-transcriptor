<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranscriptionRequest;
use App\Jobs\ProcessAudioJob;
use App\Models\Transcription;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class TranscriptionController extends Controller
{
    public function store(StoreTranscriptionRequest $request): JsonResponse
    {
        $userId = $request->user()->id; //TODO: Em um cenário fora de testes, pegar o user ID real.
        $audioFile = $request->file('audio');

        $transcription = Transcription::create([
            'user_id' => $userId,
            'status' => 'pending',
            'original_file_path' => 'placeholder',
        ]);

        $extension = $audioFile->getClientOriginalExtension();
        $filePath = "users/{$userId}/{$transcription->id}/original.{$extension}";

        Storage::disk('minio')->put(
            $filePath,
            $audioFile
        );

        $transcription->update(['original_file_path' => $filePath]);

        ProcessAudioJob::dispatch($transcription);


        return response()->json([
            'message' => 'File uploaded successfully. Transcription is pending.',
            'transcription_id' => $transcription->id,
            'status' => $transcription->status,
        ], 201);
    }
}
