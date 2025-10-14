
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:underline">Dashboard</a>
                <span class="text-gray-500 mx-2">/</span>
                <span>{{ basename($transcription->original_file_path) }}</span>
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Voltar para a lista</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8"
                 x-data="{ textContent: @js($textContent), copied: false }">

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Transcrição Finalizada</h3>

                    <div>
                        <button
                            @click="
                                navigator.clipboard.writeText(textContent);
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            "
                            class="relative px-3 py-1.5 text-sm bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                        >
                            <span x-show="!copied">Copiar Texto</span>
                            <span x-show="copied" class="text-green-600 font-semibold">Copiado!</span>
                        </button>
                    </div>
                </div>

                <div class="prose max-w-none bg-gray-50 p-6 rounded-md whitespace-pre-wrap text-gray-800">
                    {{ $textContent }}
                </div>

                <div class="mt-8 border-t pt-6">
                    <form method="POST" action="{{ route('transcriptions.destroy', $transcription) }}" onsubmit="return confirm('Tem a certeza de que deseja apagar esta transcrição? Esta ação não pode ser desfeita.');">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 rounded p-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Apagar Transcrição
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
