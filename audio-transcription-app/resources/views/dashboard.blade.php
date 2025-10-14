<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard de Transcrições
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" x-data="uploadForm()">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Nova Transcrição</h3>

                <form @submit.prevent="submit">
                    <div>
                        <label for="audio_file" class="sr-only">Escolher ficheiro</label>
                        <input type="file" id="audio_file" @change="file = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                        <p class="mt-1 text-sm text-gray-500" id="file_input_help">MP3, WAV, M4A, etc. Tamanho máximo: 500MB.</p>
                    </div>

                    <div x-show="isUploading" class="w-full bg-gray-200 rounded-full mt-4">
                        <div class="bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" :style="`width: ${progress}%`" x-text="`${progress}%`"></div>
                    </div>
                    
                    <div x-show="errorMessage" x-text="errorMessage" class="text-sm text-red-600 mt-2"></div>

                    <div class="mt-6 flex items-center">
                        <button type="submit"
                                :disabled="isUploading"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <span x-show="!isUploading">Enviar Áudio</span>
                            <span x-show="isUploading">Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Histórico</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ficheiro</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($transcriptions as $transcription)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ basename($transcription->original_file_path) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @switch($transcription->status)
                                                @case('completed') bg-green-100 text-green-800 @break
                                                @case('processing') bg-yellow-100 text-yellow-800 @break
                                                @case('failed') bg-red-100 text-red-800 @break
                                                @default bg-gray-100 text-gray-800
                                            @endswitch
                                        ">
                                            {{ ucfirst($transcription->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $transcription->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if ($transcription->status === 'completed')
                                            <a href="{{ route('transcriptions.show', $transcription) }}" class="text-indigo-600 hover:text-indigo-900">Ver</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        Você ainda não enviou nenhum áudio. Comece enviando um ficheiro acima.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $transcriptions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

