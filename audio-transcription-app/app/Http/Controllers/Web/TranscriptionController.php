<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transcription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TranscriptionController extends Controller
{
    public function index(Request $request)
    {
        $transcriptions = $request->user()->transcriptions()->latest()->paginate(10);
        return view('dashboard', ['transcriptions' => $transcriptions]);
    }

    public function show(Transcription $transcription)
    {
        $this->authorize('view', $transcription);

        $textContent = '';
        if ($transcription->status === 'completed') {
            $textContent = Storage::disk('minio')->get($transcription->transcription_file_path);
        }

        return view('transcriptions.show', [
            'transcription' => $transcription,
            'textContent' => $textContent,
        ]);
    }
}