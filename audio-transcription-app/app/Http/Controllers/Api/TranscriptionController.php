<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranscriptionRequest;
use App\Jobs\ProcessAudioJob;
use App\Models\Transcription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\TranscriptionResource; 
class TranscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection 
    {
        $user = $request->user();

        $transcriptions = $user->transcriptions()
                            ->latest()
                            ->paginate(15);

        return TranscriptionResource::collection($transcriptions);

    }
    public function store(StoreTranscriptionRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $audioFile = Request::file('audio');

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
