<?php

namespace App\Jobs;

use File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Transcription;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Log;
use Throwable;


class ProcessAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public int $timeout = 3600;

    public int $tries = 3;


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
            $this->transcription->update(['status' => 'processing']);

            $tempDir = storage_path('app/temp/' . $this->transcription->id);
            $chunksDir = $tempDir . '/chunks';
            File::makeDirectory($tempDir, 0755, true, true);
            File::makeDirectory($chunksDir, 0755, true, true);

            $originalFilePath = $tempDir . '/original.tmp';

            $fileContents = Storage::disk('minio')->get($this->transcription->original_file_path);
            File::put($originalFilePath, $fileContents);

            $command = sprintf(
                'ffmpeg -i %s -f segment -segment_time 600 -c copy %s/chunk_%%03d.mp3',
                escapeshellarg($originalFilePath),
                escapeshellarg($chunksDir)
            );

            Log::info("Running FFmpeg for transcription {$this->transcription->id}: {$command}");
            Process::run($command)->throw();

            $chunkFiles = File::glob($chunksDir . '/*.mp3');
            $this->transcription->update(['total_chunks' => count($chunkFiles)]);


            collect($chunkFiles)->each(function ($chunkFile) {
                $chunkFileName = basename($chunkFile);
                $minioChunkPath = "users/{$this->transcription->user_id}/{$this->transcription->id}/chunks/{$chunkFileName}";

                Storage::disk('minio')->put(
                    $minioChunkPath,
                    File::getContents($chunkFile)
                );

                    // TODO: Invocar a Lambda aqui, passando o $minioChunkPath
                Log::info("Chunk uploaded to MinIO: {$minioChunkPath}");
            });




        } catch (Throwable $e) {
            Log::error($e->getMessage());
            $this->fail($e->getMessage());
        } finally {
            File::deleteDirectory($tempDir);
            Log::info("Cleaned up temp directory: {$tempDir}");
        }

    }

    public function failed(Throwable $exception): void
    {
        $this->transcription->update(['status' => 'failed']);
        Log::error("Job failed for transcription {$this->transcription->id}: " . $exception->getMessage());
    }
}
