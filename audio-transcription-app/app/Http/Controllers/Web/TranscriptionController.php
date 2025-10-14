<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transcription;
use Illuminate\Http\RedirectResponse;
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

    public function destroy(Transcription $transcription): RedirectResponse 
    {
        $this->authorize('delete', $transcription);

        $pathsToDelete = [];

        if($transcription->original_file_path) {
            $pathsToDelete[] = $transcription->original_file_path;
        }

        if($transcription->transcription_file_path) { 
            $pathsToDelete[] = $transcription->transcription_file_path;
        }

        if (!empty($pathsToDelete)) {
            Storage::disk('minio')->delete($pathsToDelete);
        }

        $transcription->delete();

     return redirect()->route('dashboard')
            ->with('success', 'Transcrição apagada com sucesso.');
    
    }
}