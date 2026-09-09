<?php

namespace App\Http\Controllers;

use App\Billing\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $gateway): Response
    {
        $gateway->handleWebhook(
            $request->getContent(),
            (string) $request->header('Stripe-Signature', ''),
        );

        return response()->noContent();
    }
}
