<?php

namespace App\Jobs;

use App\Models\Transcription;
use App\Services\CloudRunInvoker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
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

    public function handle(CloudRunInvoker $invoker): void
    {
        $tempDir = null;
        try {
            $this->transcription->update(['status' => 'processing']);

            $tempDir = storage_path('app/temp/' . $this->transcription->id);
            $chunksDir = $tempDir . '/chunks';
            File::makeDirectory($chunksDir, 0755, true, true);
            $originalFilePath = $tempDir . '/original.tmp';

            Log::info("Downloading original file for transcription {$this->transcription->id}...");
            $fileContents = Storage::disk('minio')->get($this->transcription->original_file_path);
            File::put($originalFilePath, $fileContents);

            $command = sprintf(
                'ffmpeg -i %s -f segment -segment_time 600 -c copy %s/chunk_%%03d.mp3',
                escapeshellarg($originalFilePath),
                escapeshellarg($chunksDir)
            );
            Log::info("Running FFmpeg: {$command}");
            Process::run($command)->throw();

            $chunkFiles = collect(File::glob($chunksDir . '/*.mp3'));
            $this->transcription->update(['total_chunks' => $chunkFiles->count()]);

            $chunkFiles->each(function (string $chunkFile) use ($invoker) {
                $chunkFileName = basename($chunkFile);
                $minioChunkPath = "users/{$this->transcription->user_id}/{$this->transcription->id}/chunks/{$chunkFileName}";

                Storage::disk('minio')->put(
                    $minioChunkPath,
                    File::get($chunkFile)
                );
                Log::info("Uploaded chunk to MinIO: {$minioChunkPath}");

                $payload = [
                    'bucket' => config('filesystems.disks.minio.bucket'),
                    'key' => $minioChunkPath,
                ];

                Log::info("Invoking Cloud Run for chunk: {$minioChunkPath}");
                $invoker->invoke($payload);
            });

        } catch (Throwable $e) {
            $this->fail($e);
        } finally {
            if ($tempDir && File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
                Log::info("Cleaned up temp directory: {$tempDir}");
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->transcription->update(['status' => 'failed']);
        Log::error("Job failed for transcription {$this->transcription->id}: " . $exception->getMessage());
    }
}