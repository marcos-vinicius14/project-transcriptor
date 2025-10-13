<?php


namespace App\Jobs;

use App\Models\Transcription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MergeTranscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Transcription $transcription)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting merge process for transcription {$this->transcription->id}");

            // A nossa nomenclatura 'chunk_001.mp3', 'chunk_002.mp3' garante a ordem correta.
            $chunks = $this->transcription->chunks()
                ->orderBy('chunk_path', 'asc')
                ->get();

            $fullText = $chunks->pluck('content')->implode("\n\n");

            $finalPath = "users/{$this->transcription->user_id}/{$this->transcription->id}/full_transcription.txt";
            Storage::disk('minio')->put($finalPath, $fullText);
            Log::info("Full transcription saved to: {$finalPath}");

            $this->transcription->update([
                'status' => 'completed',
                'transcription_file_path' => $finalPath,
            ]);

            $chunkPaths = $chunks->pluck('chunk_path')->all();
            Storage::disk('minio')->delete($chunkPaths);
            $this->transcription->chunks()->delete();
            Log::info("Cleaned up individual chunks for transcription {$this->transcription->id}");
            
            // TODO: Futuramente, despachar notificação para o usuário (e-mail, websocket, etc.)

        } catch (Throwable $e) {
            $this->fail($e);
        }
    }


    public function failed(Throwable $exception): void
    {
        // Se a fusão falhar permanentemente, marcamos como falha.
        $this->transcription->update(['status' => 'failed']);
        Log::error("Merge job failed for transcription {$this->transcription->id}: " . $exception->getMessage());
    }
}