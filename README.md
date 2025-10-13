# **App de Transcrição de Áudio de Longa Duração**

Este é um sistema robusto projetado para transcrever arquivos de áudio longos (50-60 minutos) de forma assíncrona, escalável e resiliente. A aplicação utiliza uma arquitetura de microserviços para desacoplar a aplicação principal (Laravel) da tarefa computacionalmente intensiva de transcrição (Python \+ Whisper em um container no Google Cloud Run).

## **Funcionalidades Principais**

* **Upload Eficiente:** Suporte para upload de arquivos de áudio grandes (até 500MB) via streaming, garantindo baixo consumo de memória no servidor.  
* **Processamento Assíncrono:** Todo o processo de transcrição é executado em background através de filas (jobs), proporcionando uma resposta imediata ao usuário e garantindo que o sistema não trave com tarefas longas.  
* **Escalabilidade:** O serviço de transcrição é um container independente no Google Cloud Run, que pode escalar horizontalmente para lidar com múltiplos áudios simultaneamente, sem impactar a performance da aplicação principal.  
* **Segurança:** A comunicação entre os serviços é protegida. As chamadas para o serviço de transcrição são autenticadas via Google ID Tokens, e o endpoint de webhook que recebe os resultados é protegido por assinaturas HMAC.

## **Arquitetura do Sistema**

O fluxo de dados é orquestrado para garantir resiliência e desacoplamento entre os serviços.

                  \+-------------------------+      \+-------------------------+  
                  |                         |      |                         |  
        \+--------\>|     Laravel App (API)   \+-----\>|      MinIO Storage      |  
        |         |                         |      |   (Armazenamento S3)    |  
        |         \+-------------------------+      \+-------------------------+  
        |                    | (1) Upload                  | (2) Arquivo Original  
\+-------+-------+            |                             |  
|               |            | (3) Despacha Job            |  
|    Usuário    |            v                             |  
|               |    \+-------+--------+                    |  
\+---------------+    |                |                    |  
        ^            |   Redis Queue  |                    |  
        |            |                |                    |  
        |            \+-------+--------+                    |  
        |                    |                             |  
        |                    | (4) Processa Job            |  
        |                    v                             |  
        |         \+-------------------------+              |  
        |         |                         |              |  
        |         |   ProcessAudioJob (PHP) |--------------+  
        |         |                         | (5) Baixa original,  
        |         \+-------------------------+     divide e sobe chunks  
        |                    |  
        |                    | (6) Invoca serviço para cada chunk  
        |                    v  
        |         \+-------------------------+  
        |         |                         |  
        |         |  Cloud Run Service (Py) |  
        |         |    (Flask \+ Whisper)    |  
        |         \+-------------------------+  
        |                    |  
        |                    | (7) Envia resultado via Webhook  
        |                    v  
        |         \+-------------------------+  
        |         |                         |  
        |         | WebhookController (PHP) |  
        |         | (com Middleware HMAC)   |  
        |         \+-------------------------+  
        |                    |  
        |                    | (8) Despacha Job de Fusão  
        |                    v  
        |         \+-------------------------+      \+-------------------------+  
        |         |                         |      |                         |  
        |         |  MergeTranscriptionJob  \+-----\>|      MinIO Storage      |  
        |         |        (PHP)            |      |   (Salva .txt Final)    |  
        \+---------+                         |      \+-------------------------+  
                  \+-------------------------+  
                      (9) Notifica Usuário

## **Stack de Tecnologias**

* **Backend Principal:** PHP 8.3+, Laravel 12  
* **Serviço de Transcrição:** Python 3.11, Flask, OpenAI Whisper  
* **Banco de Dados:** PostgreSQL 15+  
* **Filas e Cache:** Redis  
* **Armazenamento de Arquivos:** MinIO (S3-compatible)  
* **Infraestrutura:** Docker, Docker Compose (para ambiente local via Laravel Sail), Google Cloud Run (para o serviço de transcrição em produção).

## **Configuração do Ambiente Local**

O projeto utiliza o **Laravel Sail** para simplificar a configuração do ambiente de desenvolvimento com Docker.

1. **Clonar o Repositório:**
 ```
   git clone https://github.com/marcos-vinicius14/project-transcriptor.git 
```

3. Configurar o .env:  
   Copie o arquivo de exemplo e preencha as variáveis necessárias.  
   cp .env.example .env

4. Iniciar os Containers:  
   Este comando irá construir e iniciar todos os serviços definidos no docker-compose.yml (Laravel, PostgreSQL, Redis, MinIO).  
   ./vendor/bin/sail up \-d

5. **Instalar Dependências:**  
   ./vendor/bin/sail composer install

6. **Gerar a Chave da Aplicação:**  
   ./vendor/bin/sail artisan key:generate

7. Rodar as Migrações:  
   Este comando criará todas as tabelas no banco de dados PostgreSQL.  
   ./vendor/bin/sail artisan migrate

Após estes passos, a aplicação estará acessível em http://localhost. O console do MinIO estará acessível em http://localhost:9001.

## **Componentes do Sistema (Deep Dive)**

### **1\. O Fluxo de Upload (TranscriptionController)**

* **Responsabilidade:** Lidar com a requisição inicial de upload do usuário.  
* **Funcionamento:**  
  * Valida o arquivo de áudio (tamanho, tipo).  
  * Usa streaming para enviar o arquivo diretamente para o MinIO, sem sobrecarregar a memória ou o disco do servidor.  
  * Cria um registro inicial na tabela transcriptions com o status pending.  
  * Ao final, despacha o ProcessAudioJob para a fila, passando a instância da transcrição recém-criada.

### **2\. O Maestro (ProcessAudioJob)**

* **Responsabilidade:** Orquestrar todo o processo de preparação do áudio para a transcrição.  
* **Funcionamento:**  
  1. Atualiza o status da transcrição para processing.  
  2. Baixa o arquivo de áudio original do MinIO para um diretório temporário no container.  
  3. Usa **FFmpeg** para dividir o áudio em "chunks" (pedaços) de 10 minutos.  
  4. Faz o upload de cada chunk individualmente para uma subpasta no MinIO.  
  5. Para cada chunk, invoca o serviço no **Google Cloud Run** através do CloudRunInvoker, enviando a localização do chunk a ser processado.

### **3\. O Especialista (Serviço Cloud Run)**

* **Responsabilidade:** Executar a transcrição de um único pedaço de áudio.  
* **Funcionamento:**  
  1. É um container independente com Python, Flask e Whisper.  
  2. Recebe uma requisição HTTP POST segura, contendo o caminho do arquivo de áudio no MinIO.  
  3. Baixa o chunk de áudio.  
  4. Executa o modelo **Whisper** para gerar o texto.  
  5. Envia o texto transcrito de volta para a aplicação Laravel através de um **Webhook**.

### **4\. A Ponte de Volta (WebhookController)**

* **Responsabilidade:** Receber os resultados parciais do serviço de transcrição de forma segura.  
* **Funcionamento:**  
  * **Segurança:** A rota é protegida pelo middleware VerifyWebhookSignature, que valida uma assinatura HMAC enviada no header da requisição. Isso garante que somente nosso serviço Cloud Run possa enviar dados.  
  * **Lógica:**  
    1. Salva o texto recebido na tabela transcription\_chunks.  
    2. Verifica se o número de chunks recebidos é igual ao total esperado para aquela transcrição.  
    3. Se todos os chunks foram recebidos, ele despacha o MergeTranscriptionJob para finalizar o processo.

### **5\. O Finalizador (MergeTranscriptionJob)**

* **Responsabilidade:** Consolidar todos os pedaços de texto em um único arquivo final.  
* **Funcionamento:**  
  1. Busca todos os registros da tabela transcription\_chunks em ordem.  
  2. Concatena os textos em uma única string.  
  3. Salva o texto completo em um arquivo .txt no MinIO.  
  4. Atualiza o registro principal na tabela transcriptions para o status completed, incluindo o caminho para o arquivo final.  
  5. Realiza a limpeza, apagando os chunks individuais do MinIO e do banco de dados para economizar espaço.
