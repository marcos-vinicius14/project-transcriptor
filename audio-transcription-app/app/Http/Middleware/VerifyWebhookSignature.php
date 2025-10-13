<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Webhook-Signature');
        $secret = config('services.webhook.secret');

        if (!$signature || !$secret) {
            abort(403, 'Webhook signature header not found or secret not configured.');
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $request->getContent(),
            $secret
        );

        if (!hash_equals($expectedSignature, $signature)) {
            abort(403, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}