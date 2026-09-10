<?php

namespace App\Billing\Gateways\MercadoPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            Log::warning('cuotas.mercadopago.webhook_secret_missing');

            abort(503);
        }

        $signature = $request->header('x-signature', '');
        $requestId = $request->header('x-request-id');
        $dataId = $request->query('data.id')
            ?? $request->query('data_id')
            ?? $request->input('data.id');

        parse_str(str_replace(',', '&', $signature), $signatureParts);
        $timestamp = $signatureParts['ts'] ?? null;
        $providedHash = $signatureParts['v1'] ?? null;

        $manifest = '';

        if (is_scalar($dataId)) {
            $manifest .= 'id:'.strtolower((string) $dataId).';';
        }

        if (is_string($requestId) && $requestId !== '') {
            $manifest .= 'request-id:'.$requestId.';';
        }

        if (is_scalar($timestamp)) {
            $manifest .= 'ts:'.$timestamp.';';
        }

        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        if (! is_string($providedHash) || ! hash_equals($expectedHash, $providedHash)) {
            Log::warning('cuotas.mercadopago.webhook_invalid');

            abort(401);
        }

        $type = $request->string('type')->toString();

        if ($type !== '' && is_scalar($dataId)) {
            ProcessMercadoPagoWebhook::dispatch($type, (string) $dataId);
        }

        return response()->json(['received' => true]);
    }
}
