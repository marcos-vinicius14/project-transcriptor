
function uploadForm() {
    return {
        file: null,
        isUploading: false,
        progress: 0,
        errorMessage: '',
        submit() {
            if (!this.file) {
                this.errorMessage = 'Por favor, selecione um arquivo.';
                return;
            }

            this.isUploading = true;
            this.progress = 0;
            this.errorMessage = '';

            let formData = new FormData();
            formData.append('audio', this.file);

            axios.post('/api/transcriptions', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    this.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                }
            }).then(response => {
                alert('Arquivo enviado com sucesso! A transcrição está sendo processada.');
                window.location.reload();
            }).catch(error => {
                if (error.response && error.response.status === 422) {
                    this.errorMessage = Object.values(error.response.data.errors).join(' ');
                } else {
                    this.errorMessage = 'Ocorreu um erro inesperado. Tente novamente.';
                }
            }).finally(() => {
                this.isUploading = false;
            });
        }
    }
}

window.uploadForm = uploadForm;
