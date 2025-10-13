<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\MergeTranscriptionJob; 
use App\Models\Transcription;
use App\Models\TranscriptionChunk;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $request->validate([
            'transcription_chunk_key' => 'required|string',
            'text' => 'required|string',
        ]);

        $chunkPath = $payload['transcription_chunk_key'];
        $transcribedText = $payload['text'];

        //TODO: Extrai o ID da transcrição do caminho do chunk
        // Ex: "users/1/uuid-da-transcricao/chunks/chunk_001.mp3"
        preg_match('/\/([a-f0-9\-]+)\/chunks\//', $chunkPath, $matches);
        $transcriptionId = $matches[1] ?? null;

        if (!$transcriptionId || !($transcription = Transcription::find($transcriptionId))) {
            Log::warning('Webhook received for unknown transcription.', ['path' => $chunkPath]);
            return response('Transcription not found.', 404);
        }

        TranscriptionChunk::updateOrCreate(
            ['transcription_id' => $transcription->id, 'chunk_path' => $chunkPath],
            ['content' => $transcribedText]
        );

        $processedChunksCount = $transcription
            ->chunks()
            ->whereNotNull('content')
            ->count();

        if ($processedChunksCount >= $transcription->total_chunks) {
            Log::info("All chunks received for transcription {$transcription->id}. Dispatching merge job.");
            
            MergeTranscriptionJob::dispatch($transcription);
        }

        return response('Webhook received.', 200);
    }
}
