<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class CloudRunInvoker
{
    public function invoke(array $payload): void
    {
        $cloudRunUrl = config('services.cloud_run.url');

        $credentials = new ServiceAccountCredentials(
            null, 
            config('services.google.credentials_path'),
            null, 
            $cloudRunUrl
        );

        $middleware = new AuthTokenMiddleware($credentials);
        $stack = HandlerStack::create();
        $stack->push($middleware);
        $client = new Client(['handler' => $stack, 'auth' => 'google_auth']);

        $client->post($cloudRunUrl . '/transcribe', [
            'json' => $payload,
        ]);
    }

}