<?php

namespace App\Http\Controllers;

use App\Actions\Payments\ReconcilePayMongoWebhook;
use App\Services\Payments\PayMongoGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use LogicException;
use Throwable;

class PayMongoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PayMongoGateway $payMongoGateway,
        ReconcilePayMongoWebhook $reconcilePayMongoWebhook,
    ): JsonResponse {
        $rawPayload = $request->getContent();
        $signatureHeader = $request->header(
            'Paymongo-Signature',
        );

        if (
            $rawPayload === ''
            || ! is_string($signatureHeader)
            || trim($signatureHeader) === ''
        ) {
            return response()->json([
                'message' => 'Invalid PayMongo webhook signature.',
            ], 401);
        }

        try {
            $liveMode = $payMongoGateway->webhookLiveMode();

            if (! $payMongoGateway->verifyWebhookSignature(
                rawPayload: $rawPayload,
                signatureHeader: $signatureHeader,
                liveMode: $liveMode,
            )) {
                return response()->json([
                    'message' => 'Invalid PayMongo webhook signature.',
                ], 401);
            }
        } catch (LogicException $exception) {
            report($exception);

            return response()->json([
                'message' => 'PayMongo webhook verification is unavailable.',
            ], 503);
        }

        try {
            $payload = json_decode(
                $rawPayload,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return response()->json([
                'message' => 'Invalid PayMongo webhook payload.',
            ], 400);
        }

        if (! is_array($payload)) {
            return response()->json([
                'message' => 'Invalid PayMongo webhook payload.',
            ], 400);
        }

        if (
            data_get($payload, 'data.livemode')
            !== $liveMode
        ) {
            return response()->json([
                'message' => 'PayMongo webhook mode does not match this environment.',
            ], 409);
        }

        $eventType = data_get(
            $payload,
            'data.type',
        );

        if (! is_string($eventType)) {
            return response()->json([
                'message' => 'Invalid PayMongo webhook event type.',
            ], 400);
        }

        if ($eventType !== 'checkout_session.payment.paid') {
            return response()->json([
                'received' => true,
                'ignored' => true,
            ]);
        }

        try {
            $reconcilePayMongoWebhook->handle(
                payload: $payload,
                payloadHash: hash(
                    'sha256',
                    $rawPayload,
                ),
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'PayMongo webhook reconciliation failed.',
            ], 409);
        }

        return response()->json([
            'received' => true,
        ]);
    }
}
