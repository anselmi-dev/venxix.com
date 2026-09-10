<?php

namespace App\Billing\Gateways\DlocalGo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DlocalGoWebhookController extends Controller
{
    public function __invoke(Request $request, DlocalGoGateway $gateway): Response
    {
        $gateway->handleWebhook(
            $request->getContent(),
            (string) $request->header('Authorization', ''),
        );

        return response()->noContent();
    }
}
