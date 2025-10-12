# cloud-run-whisper/app.py

import os
import boto3
import whisper
import requests
import hashlib
import hmac
import logging
from flask import Flask, request, jsonify

app = Flask(__name__)

logging.basicConfig(level=logging.INFO)

logging.info("Loading Whisper model...")
model = whisper.load_model("base")
logging.info("Whisper model loaded.")

@app.route("/", methods=["GET"])
def health_check():
    """Endpoint básico para verificar se o serviço está de pé."""
    return "Whisper Transcription Service is running.", 200

@app.route("/transcribe", methods=["POST"])
def transcribe_audio():
    """Endpoint principal que recebe o job de transcrição."""
    
    data = request.get_json()
    if not data or 'bucket' not in data or 'key' not in data:
        return jsonify({"error": "Invalid payload. 'bucket' and 'key' are required."}), 400

    bucket_name = data['bucket']
    chunk_key = data['key']
    
    try:
        webhook_url = os.environ['LARAVEL_WEBHOOK_URL']
        webhook_secret = os.environ['WEBHOOK_SECRET']

        logging.info(f"Processing chunk: s3://{bucket_name}/{chunk_key}")

        s3_client = boto3.client(
            's3',
            endpoint_url=os.environ['MINIO_ENDPOINT'],
            aws_access_key_id=os.environ['MINIO_KEY'],
            aws_secret_access_key=os.environ['MINIO_SECRET']
        )
        local_audio_path = f"/tmp/{os.path.basename(chunk_key)}"

        logging.info(f"Downloading audio chunk to {local_audio_path}...")
        s3_client.download_file(bucket_name, chunk_key, local_audio_path)
        logging.info("Download complete.")

        logging.info("Starting transcription...")
        result = model.transcribe(local_audio_path, fp16=False)
        transcribed_text = result["text"]
        logging.info("Transcription complete.")

        payload = {
            'transcription_chunk_key': chunk_key,
            'text': transcribed_text
        }
        signature = hmac.new(webhook_secret.encode('utf-8'), str(payload).encode('utf-8'), hashlib.sha256).hexdigest()
        headers = {'Content-Type': 'application/json', 'X-Webhook-Signature': signature}

        logging.info(f"Sending transcription to webhook: {webhook_url}")
        response = requests.post(webhook_url, json=payload, headers=headers, timeout=10)
        response.raise_for_status()

        return jsonify({"status": "success", "message": "Transcription successful and webhook sent."}), 200

    except Exception as e:
        logging.error(f"Error processing transcription: {str(e)}")
        return jsonify({"error": "Internal Server Error"}), 500
    finally:
        if os.path.exists(local_audio_path):
            os.remove(local_audio_path)

if __name__ == "__main__":
    app.run(debug=True, host="0.0.0.0", port=int(os.environ.get("PORT", 8080)))