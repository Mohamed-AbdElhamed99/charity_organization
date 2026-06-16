<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Services\DonationWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly DonationWebhookService $webhookService,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = $this->gateway->constructWebhookEvent($payload, $sigHeader);
        } catch (UnexpectedValueException) {
            return response('Invalid signature', 400);
        }

        try {
            Log::info("Event : " , [$event]);
            $this->webhookService->handle($event);
        } catch (\Throwable) {
            return response('Processing failed', 500);
        }

        return response('OK', 200);
    }
}