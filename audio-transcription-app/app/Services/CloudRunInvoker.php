<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class CloudRunInvoker
{
    public function invoke(array $payload): void
    {
        $cloudRunUrl = config('services.cloud_run.url');

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/cloud-platform',
            config('services.google.credentials_path')
        );
        $credentials->setTargetAudience($cloudRunUrl);

        $handler = HttpHandlerFactory::build(new Client());
        $authedHttpHandler = $credentials->authorize($handler);

        $client = new Client(['handler' => HandlerStack::create($authedHttpHandler)]);

        $client->post($cloudRunUrl . '/transcribe', [
            'json' => $payload,
            'timeout' => 0,
        ]);
    }

}