<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePushSubscriptionRequest;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PushSubscriptionController extends Controller
{
    public function store(
        StorePushSubscriptionRequest $request,
        PushNotificationService $pushNotifications,
    ): JsonResponse {
        abort_unless($pushNotifications->isConfigured(), 503);

        $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $request->string('endpoint')->toString()],
            [
                'public_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'content_encoding' => $request->input('content_encoding'),
            ],
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): Response
    {
        $endpoint = $request->string('endpoint')->toString();

        abort_if($endpoint === '', 422);

        $request->user()
            ->pushSubscriptions()
            ->where('endpoint', $endpoint)
            ->delete();

        return response()->noContent();
    }
}
