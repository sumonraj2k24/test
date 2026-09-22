<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Services\PaymentService;

final class WebhookController
{
    public function stripe(Request $request, array $params = []): void
    {
        $this->json(function () use ($request): array {
            $payload = file_get_contents('php://input') ?: '';
            $secret = (string) setting('stripe_webhook_secret', '');
            if ($secret !== '') {
                $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
                if (!$this->stripeSignature($payload, $signature, $secret)) {
                    return ['ok' => false, 'error' => 'invalid signature'];
                }
            }
            $event = json_decode($payload, true);
            $object = $event['data']['object'] ?? [];
            $number = $object['client_reference_id'] ?? null;
            if (($event['type'] ?? '') === 'checkout.session.completed' && $number && ($object['payment_status'] ?? '') === 'paid') {
                $order = Database::first('SELECT * FROM orders WHERE number = ?', [$number]);
                if ($order) {
                    PaymentService::markPaid((int) $order['id'], 'stripe', $object['id'] ?? null, null, ['source' => 'webhook']);
                }
            }
            return ['ok' => true];
        });
    }

    public function paypal(Request $request, array $params = []): void
    {
        $this->json(fn (): array => ['ok' => true, 'note' => 'PayPal capture is confirmed on the return URL.']);
    }

    public function mollie(Request $request, array $params = []): void
    {
        $this->json(function (): array {
            $id = $_POST['id'] ?? '';
            if ($id === '') {
                return ['ok' => false];
            }
            $order = Database::first('SELECT * FROM orders WHERE gateway_ref = ?', [$id]);
            if ($order) {
                $verified = PaymentService::verifyReturn('mollie', ['order' => $order['number']]);
                return ['ok' => ($verified['status'] ?? '') === 'paid'];
            }
            return ['ok' => false];
        });
    }

    public function paystack(Request $request, array $params = []): void
    {
        $this->json(function (): array {
            $payload = file_get_contents('php://input') ?: '';
            $secret = (string) setting('paystack_secret', '');
            $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
            if ($secret !== '' && !hash_equals(hash_hmac('sha512', $payload, $secret), $signature)) {
                return ['ok' => false, 'error' => 'invalid signature'];
            }
            $event = json_decode($payload, true);
            $reference = $event['data']['reference'] ?? '';
            if (($event['event'] ?? '') === 'charge.success' && $reference) {
                $order = Database::first('SELECT * FROM orders WHERE number = ? OR gateway_ref = ?', [$reference, $reference]);
                if ($order) {
                    PaymentService::markPaid((int) $order['id'], 'paystack', $reference, null, ['source' => 'webhook']);
                }
            }
            return ['ok' => true];
        });
    }

    private function json(callable $fn): void
    {
        header('Content-Type: application/json');
        try {
            echo json_encode($fn(), json_flags());
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], json_flags());
        }
    }

    private function stripeSignature(string $payload, string $header, string $secret): bool
    {
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, '');
            $parts[$k] = $v;
        }
        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }
        $signed = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);
        return hash_equals($signed, $parts['v1']);
    }
}
