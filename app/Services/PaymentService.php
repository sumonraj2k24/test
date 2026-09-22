<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class PaymentService
{
    public const GATEWAYS = [
        'stripe' => ['label' => 'Stripe', 'kind' => 'card', 'blurb' => 'Card checkout. Sandbox accepts 4242 4242 4242 4242.'],
        'paypal' => ['label' => 'PayPal', 'kind' => 'wallet', 'blurb' => 'Approve the payment in the PayPal sandbox.'],
        'mollie' => ['label' => 'Mollie', 'kind' => 'wallet', 'blurb' => 'European methods through Mollie.'],
        'paystack' => ['label' => 'Paystack', 'kind' => 'card', 'blurb' => 'Cards and local methods through Paystack.'],
    ];

    public static function enabled(): array
    {
        $out = [];
        foreach (self::GATEWAYS as $key => $meta) {
            if (setting('pay_' . $key . '_enabled', '1') === '1') {
                $out[$key] = $meta + ['configured' => self::isConfigured($key)];
            }
        }
        return $out;
    }

    public static function isConfigured(string $gateway): bool
    {
        return match ($gateway) {
            'stripe' => setting('stripe_secret', '') !== '',
            'paypal' => setting('paypal_client_id', '') !== '' && setting('paypal_secret', '') !== '',
            'mollie' => setting('mollie_api_key', '') !== '',
            'paystack' => setting('paystack_secret', '') !== '',
            default => false,
        };
    }

    public static function coupon(?string $code): ?array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }
        $row = Database::first('SELECT * FROM coupons WHERE upper(code) = ?', [$code]);
        if (!$row || (int) $row['active'] !== 1) {
            return null;
        }
        if (!empty($row['expires_at']) && strtotime($row['expires_at'] . ' UTC') < time()) {
            return null;
        }
        if ($row['max_uses'] !== null && (int) $row['uses'] >= (int) $row['max_uses']) {
            return null;
        }
        return $row;
    }

    public static function quote(array $courses, ?string $couponCode): array
    {
        $subtotal = 0;
        foreach ($courses as $course) {
            $subtotal += (int) $course['price_cents'];
        }
        $coupon = self::coupon($couponCode);
        $discount = 0;
        if ($coupon && $subtotal > 0) {
            $discount = $coupon['type'] === 'fixed'
                ? min($subtotal, (int) $coupon['value'])
                : (int) round($subtotal * ((int) $coupon['value'] / 100));
        }
        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'coupon' => $coupon,
            'coupon_error' => ($couponCode && !$coupon) ? 'That coupon is not active.' : null,
        ];
    }

    public static function startOrder(int $userId, array $courses, ?string $couponCode, string $gateway): array
    {
        $courses = array_values(array_filter($courses, fn (array $course): bool => !enrollment_for($userId, (int) $course['id'])));
        if ($courses === []) {
            throw new \RuntimeException('Nothing left to buy. You may already be enrolled.');
        }
        $quote = self::quote($courses, $couponCode);
        if ($quote['total'] > 0 && !isset(self::GATEWAYS[$gateway])) {
            throw new \RuntimeException('Choose a payment method.');
        }
        $rate = (int) setting('platform_commission_percent', '20');
        $number = 'MRD-' . gmdate('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $orderId = Database::transaction(function () use ($userId, $courses, $quote, $gateway, $rate, $number): int {
            $orderId = Database::insert('orders', [
                'user_id' => $userId,
                'number' => $number,
                'subtotal_cents' => $quote['subtotal'],
                'discount_cents' => $quote['discount'],
                'total_cents' => $quote['total'],
                'currency' => (string) setting('currency', 'USD'),
                'gateway' => $quote['total'] === 0 ? 'free' : $gateway,
                'coupon_code' => $quote['coupon']['code'] ?? null,
                'status' => 'pending',
                'created_at' => now(),
            ]);
            $remainingDiscount = $quote['discount'];
            $count = count($courses);
            foreach ($courses as $index => $course) {
                $price = (int) $course['price_cents'];
                if ($index === $count - 1) {
                    $itemDiscount = $remainingDiscount;
                } else {
                    $itemDiscount = $quote['subtotal'] > 0
                        ? (int) round($quote['discount'] * ($price / $quote['subtotal']))
                        : 0;
                    $remainingDiscount -= $itemDiscount;
                }
                $net = max(0, $price - $itemDiscount);
                $fee = (int) round($net * $rate / 100);
                Database::insert('order_items', [
                    'order_id' => $orderId,
                    'course_id' => (int) $course['id'],
                    'price_cents' => $net,
                    'commission_rate' => $rate,
                    'platform_fee_cents' => $fee,
                    'instructor_earning_cents' => $net - $fee,
                    'instructor_id' => (int) $course['instructor_id'],
                ]);
            }
            return $orderId;
        });
        $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if ((int) $order['total_cents'] === 0) {
            self::markPaid((int) $order['id'], 'free', 'FREE-' . $order['number'], null, ['note' => 'zero total']);
            $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
        }
        return $order;
    }

    public static function markPaid(int $orderId, string $gateway, ?string $ref, ?string $last4, array $payload = []): void
    {
        Database::transaction(function () use ($orderId, $gateway, $ref, $last4, $payload): void {
            $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
            if (!$order || $order['status'] === 'paid') {
                return;
            }
            Database::update('orders', [
                'status' => 'paid',
                'gateway' => $gateway,
                'gateway_ref' => $ref,
                'paid_at' => now(),
            ], 'id = ?', [$orderId]);
            Database::insert('transactions', [
                'order_id' => $orderId,
                'gateway' => $gateway,
                'gateway_ref' => $ref,
                'amount_cents' => (int) $order['total_cents'],
                'status' => 'paid',
                'last4' => $last4,
                'payload_json' => json_encode($payload, json_flags()),
                'created_at' => now(),
            ]);
            if (!empty($order['coupon_code'])) {
                Database::execute('UPDATE coupons SET uses = uses + 1 WHERE upper(code) = upper(?)', [$order['coupon_code']]);
            }
            $items = Database::select('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            foreach ($items as $item) {
                self::enroll((int) $order['user_id'], (int) $item['course_id'], $orderId);
                NotificationService::push(
                    (int) $item['instructor_id'],
                    'sale',
                    'New enrollment',
                    'A student enrolled. Your earning on this sale is ' . money((int) $item['instructor_earning_cents']) . '.',
                    '/studio/earnings'
                );
                Database::delete('cart_items', 'user_id = ? AND course_id = ?', [(int) $order['user_id'], (int) $item['course_id']]);
                Database::delete('wishlists', 'user_id = ? AND course_id = ?', [(int) $order['user_id'], (int) $item['course_id']]);
            }
            NotificationService::push(
                (int) $order['user_id'],
                'order',
                'You are enrolled',
                'Order ' . $order['number'] . ' is paid. The courses are on your learning shelf.',
                '/learn'
            );
        });
    }

    public static function enroll(int $userId, int $courseId, ?int $orderId = null): void
    {
        if (enrollment_for($userId, $courseId)) {
            return;
        }
        Database::insert('enrollments', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'order_id' => $orderId,
            'progress_percent' => 0,
            'enrolled_at' => now(),
        ]);
        CourseService::refreshStudents($courseId);
    }

    public static function refund(int $orderId, int $adminId): void
    {
        Database::transaction(function () use ($orderId, $adminId): void {
            $order = Database::first('SELECT * FROM orders WHERE id = ?', [$orderId]);
            if (!$order || $order['status'] !== 'paid') {
                throw new \RuntimeException('Only paid orders can be refunded.');
            }
            Database::update('orders', ['status' => 'refunded'], 'id = ?', [$orderId]);
            Database::insert('transactions', [
                'order_id' => $orderId,
                'gateway' => $order['gateway'] ?: 'manual',
                'gateway_ref' => 'refund-' . $order['number'],
                'amount_cents' => -1 * (int) $order['total_cents'],
                'status' => 'refunded',
                'last4' => null,
                'payload_json' => json_encode(['by' => $adminId], json_flags()),
                'created_at' => now(),
            ]);
            $items = Database::select('SELECT * FROM order_items WHERE order_id = ?', [$orderId]);
            foreach ($items as $item) {
                Database::delete(
                    'enrollments',
                    'user_id = ? AND course_id = ? AND order_id = ?',
                    [(int) $order['user_id'], (int) $item['course_id'], $orderId]
                );
                CourseService::refreshStudents((int) $item['course_id']);
            }
            NotificationService::push(
                (int) $order['user_id'],
                'order',
                'Order refunded',
                'Order ' . $order['number'] . ' was refunded and access was withdrawn.',
                '/orders/' . $order['id']
            );
            audit('order.refund', $order['number'], ['order_id' => $orderId]);
        });
    }

    public static function remoteCheckout(array $order): ?string
    {
        $gateway = (string) $order['gateway'];
        if (!self::isConfigured($gateway) || (int) $order['total_cents'] <= 0) {
            return null;
        }
        try {
            return match ($gateway) {
                'stripe' => self::stripe($order),
                'paypal' => self::paypal($order),
                'mollie' => self::mollie($order),
                'paystack' => self::paystack($order),
                default => null,
            };
        } catch (\Throwable $e) {
            file_put_contents(storage_path('logs/payments.log'), '[' . now() . '] ' . $e->getMessage() . "\n", FILE_APPEND);
            return null;
        }
    }

    public static function verifyReturn(string $gateway, array $query): ?array
    {
        $number = (string) ($query['order'] ?? '');
        $order = $number !== '' ? Database::first('SELECT * FROM orders WHERE number = ?', [$number]) : null;
        if (!$order) {
            return null;
        }
        if ($order['status'] === 'paid') {
            return $order;
        }
        $paid = match ($gateway) {
            'stripe' => self::verifyStripe($order, (string) ($query['session_id'] ?? '')),
            'paypal' => self::verifyPaypal($order, (string) ($query['token'] ?? '')),
            'mollie' => self::verifyMollie($order),
            'paystack' => self::verifyPaystack((string) ($query['reference'] ?? $order['gateway_ref'] ?? '')),
            default => false,
        };
        if ($paid) {
            self::markPaid((int) $order['id'], $gateway, $order['gateway_ref'], null, ['source' => 'return']);
            return Database::first('SELECT * FROM orders WHERE id = ?', [(int) $order['id']]);
        }
        return $order;
    }

    private static function stripe(array $order): ?string
    {
        $currency = strtolower((string) $order['currency']);
        $fields = [
            'mode' => 'payment',
            'success_url' => url('/checkout/return/stripe?session_id={CHECKOUT_SESSION_ID}&order=' . $order['number']),
            'cancel_url' => url('/checkout'),
            'client_reference_id' => $order['number'],
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => (int) $order['total_cents'],
            'line_items[0][price_data][product_data][name]' => 'Meridian ' . $order['number'],
        ];
        $res = HttpClient::form('POST', 'https://api.stripe.com/v1/checkout/sessions', $fields, [
            'Authorization: Bearer ' . setting('stripe_secret'),
        ]);
        $url = $res['json']['url'] ?? null;
        if ($url) {
            Database::update('orders', ['gateway_ref' => $res['json']['id'] ?? null], 'id = ?', [(int) $order['id']]);
        }
        return $url;
    }

    private static function verifyStripe(array $order, string $sessionId): bool
    {
        if ($sessionId === '' || !self::isConfigured('stripe')) {
            return false;
        }
        $res = HttpClient::get('https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId), [
            'Authorization: Bearer ' . setting('stripe_secret'),
        ]);
        return ($res['json']['payment_status'] ?? '') === 'paid'
            && ($res['json']['client_reference_id'] ?? '') === $order['number'];
    }

    private static function paypal(array $order): ?string
    {
        $base = setting('paypal_mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $token = self::paypalToken($base);
        if (!$token) {
            return null;
        }
        $res = HttpClient::json('POST', $base . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order['number'],
                'amount' => [
                    'currency_code' => $order['currency'],
                    'value' => number_format(((int) $order['total_cents']) / 100, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => url('/checkout/return/paypal?order=' . $order['number']),
                'cancel_url' => url('/checkout'),
            ],
        ], ['Authorization: Bearer ' . $token]);
        $id = $res['json']['id'] ?? null;
        if ($id) {
            Database::update('orders', ['gateway_ref' => $id], 'id = ?', [(int) $order['id']]);
        }
        foreach ($res['json']['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }

    private static function verifyPaypal(array $order, string $tokenFromQuery): bool
    {
        $id = $tokenFromQuery !== '' ? $tokenFromQuery : (string) ($order['gateway_ref'] ?? '');
        if ($id === '') {
            return false;
        }
        $base = setting('paypal_mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $token = self::paypalToken($base);
        if (!$token) {
            return false;
        }
        $res = HttpClient::request('POST', $base . '/v2/checkout/orders/' . rawurlencode($id) . '/capture', '', [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);
        return ($res['json']['status'] ?? '') === 'COMPLETED';
    }

    private static function paypalToken(string $base): ?string
    {
        $res = HttpClient::form('POST', $base . '/v1/oauth2/token', ['grant_type' => 'client_credentials'], [
            'Authorization: Basic ' . base64_encode(setting('paypal_client_id') . ':' . setting('paypal_secret')),
        ]);
        return $res['json']['access_token'] ?? null;
    }

    private static function mollie(array $order): ?string
    {
        $res = HttpClient::json('POST', 'https://api.mollie.com/v2/payments', [
            'amount' => [
                'currency' => $order['currency'],
                'value' => number_format(((int) $order['total_cents']) / 100, 2, '.', ''),
            ],
            'description' => 'Meridian ' . $order['number'],
            'redirectUrl' => url('/checkout/return/mollie?order=' . $order['number']),
            'webhookUrl' => url('/webhooks/mollie'),
            'metadata' => ['order' => $order['number']],
        ], ['Authorization: Bearer ' . setting('mollie_api_key')]);
        $id = $res['json']['id'] ?? null;
        if ($id) {
            Database::update('orders', ['gateway_ref' => $id], 'id = ?', [(int) $order['id']]);
        }
        return $res['json']['_links']['checkout']['href'] ?? null;
    }

    private static function verifyMollie(array $order): bool
    {
        $id = (string) ($order['gateway_ref'] ?? '');
        if ($id === '') {
            return false;
        }
        $res = HttpClient::get('https://api.mollie.com/v2/payments/' . rawurlencode($id), [
            'Authorization: Bearer ' . setting('mollie_api_key'),
        ]);
        return ($res['json']['status'] ?? '') === 'paid';
    }

    private static function paystack(array $order): ?string
    {
        $user = Database::first('SELECT email FROM users WHERE id = ?', [(int) $order['user_id']]);
        $res = HttpClient::json('POST', 'https://api.paystack.co/transaction/initialize', [
            'email' => $user['email'] ?? 'student@meridian.test',
            'amount' => (int) $order['total_cents'],
            'currency' => $order['currency'],
            'reference' => $order['number'],
            'callback_url' => url('/checkout/return/paystack?order=' . $order['number']),
        ], ['Authorization: Bearer ' . setting('paystack_secret')]);
        $ref = $res['json']['data']['reference'] ?? null;
        if ($ref) {
            Database::update('orders', ['gateway_ref' => $ref], 'id = ?', [(int) $order['id']]);
        }
        return $res['json']['data']['authorization_url'] ?? null;
    }

    private static function verifyPaystack(string $reference): bool
    {
        if ($reference === '' || !self::isConfigured('paystack')) {
            return false;
        }
        $res = HttpClient::get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference), [
            'Authorization: Bearer ' . setting('paystack_secret'),
        ]);
        return ($res['json']['data']['status'] ?? '') === 'success';
    }
}
