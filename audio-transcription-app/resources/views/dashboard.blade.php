<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Transcrições
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg" x-data="uploadForm()">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Nova Transcrição</h3>
                <form @submit.prevent="submit" id="uploadForm">
                    <input type="file" @change="file = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                    <p class="mt-1 text-sm text-gray-500">MP3, WAV, M4A, etc. Tamanho máximo: 500MB.</p>

                    <div x-show="isUploading" class="w-full bg-gray-200 rounded-full mt-4">
                        <div class="bg-blue-600 text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full" :style="`width: ${progress}%`" x-text="`${progress}%`"></div>
                    </div>
                    
                    <div x-show="errorMessage" x-text="errorMessage" class="text-sm text-red-600 mt-2"></div>

                    <div class="mt-4">
                        <button type="submit" :disabled="isUploading" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400">
                            <span x-show="!isUploading">Enviar</span>
                            <span x-show="isUploading">Enviando...</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Histórico</h3>
            </div>
        </div>
    </div>
</x-app-layout>