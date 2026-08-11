<?php

namespace App\Http\Controllers;

use App\Actions\Payments\ReconcilePayMongoRefundWebhook;
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
        ReconcilePayMongoRefundWebhook $reconcilePayMongoRefundWebhook,
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

        $payloadLiveMode = $this->payloadLiveMode(
            $payload,
        );

        if ($payloadLiveMode === null) {
            return response()->json([
                'message' => 'Invalid PayMongo webhook mode.',
            ], 400);
        }

        if ($payloadLiveMode !== $liveMode) {
            return response()->json([
                'message' => 'PayMongo webhook mode does not match this environment.',
            ], 409);
        }

        $eventType = $this->eventType($payload);

        if ($eventType === null) {
            return response()->json([
                'message' => 'Invalid PayMongo webhook event type.',
            ], 400);
        }

        try {
            match ($eventType) {
                'checkout_session.payment.paid' => $reconcilePayMongoWebhook->handle(
                    payload: $payload,
                    payloadHash: hash(
                        'sha256',
                        $rawPayload,
                    ),
                ),

                'refund.succeeded',
                'payment.refunded',
                'payment.refund.updated' => $reconcilePayMongoRefundWebhook->handle(
                    payload: $payload,
                ),

                default => null,
            };
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'PayMongo webhook reconciliation failed.',
            ], 409);
        }

        if (
            ! in_array(
                $eventType,
                [
                    'checkout_session.payment.paid',
                    'refund.succeeded',
                    'payment.refunded',
                    'payment.refund.updated',
                ],
                true,
            )
        ) {
            return response()->json([
                'received' => true,
                'ignored' => true,
            ]);
        }

        return response()->json([
            'received' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function eventType(
        array $payload,
    ): ?string {
        $eventType = data_get(
            $payload,
            'data.attributes.type',
        );

        if (
            ! is_string($eventType)
            || trim($eventType) === ''
        ) {
            $eventType = data_get(
                $payload,
                'data.type',
            );
        }

        if (! is_string($eventType)) {
            return null;
        }

        $eventType = trim($eventType);

        return $eventType !== ''
            ? $eventType
            : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadLiveMode(
        array $payload,
    ): ?bool {
        $liveMode = data_get(
            $payload,
            'data.attributes.livemode',
        );

        if (! is_bool($liveMode)) {
            $liveMode = data_get(
                $payload,
                'data.livemode',
            );
        }

        return is_bool($liveMode)
            ? $liveMode
            : null;
    }
}
