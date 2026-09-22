<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\CartService;
use App\Services\PaymentService;

final class CheckoutController
{
    public function show(Request $request, array $params = []): void
    {
        $courses = $this->payable();
        $quote = PaymentService::quote($courses, (string) $request->query('coupon', ''));
        view('checkout/index', [
            'title' => 'Checkout · ' . setting('site_name', 'Meridian'),
            'courses' => $courses,
            'quote' => $quote,
            'gateways' => PaymentService::enabled(),
            'coupon' => (string) $request->query('coupon', ''),
        ], 'layouts/app');
    }

    public function start(Request $request, array $params = []): void
    {
        $courses = $this->payable();
        if ($courses === []) {
            flash('error', 'Your cart is empty.');
            redirect('/cart');
        }
        $coupon = strtoupper($request->string('coupon'));
        $quote = PaymentService::quote($courses, $coupon);
        $gateway = $request->string('gateway', 'stripe');
        if ($quote['total'] > 0 && !isset(PaymentService::enabled()[$gateway])) {
            flash('error', 'Choose an enabled payment method.');
            back('/checkout');
        }
        try {
            $order = PaymentService::startOrder(Auth::id(), $courses, $coupon !== '' ? $coupon : null, $gateway);
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/checkout');
        }
        if ($order['status'] === 'paid') {
            CartService::clear(Auth::id());
            flash('success', 'You are enrolled.');
            redirect('/checkout/success/' . $order['number']);
        }
        $remote = PaymentService::remoteCheckout($order);
        if ($remote) {
            redirect($remote);
        }
        redirect('/checkout/pay/' . $order['number']);
    }

    public function gateway(Request $request, array $params): void
    {
        $order = $this->ownOrder($params['number']);
        if ($order['status'] === 'paid') {
            redirect('/checkout/success/' . $order['number']);
        }
        view('checkout/gateway', [
            'title' => 'Pay ' . $order['number'],
            'order' => $order,
            'items' => $this->items((int) $order['id']),
            'gateway' => PaymentService::GATEWAYS[$order['gateway']] ?? ['label' => 'Payment', 'kind' => 'wallet', 'blurb' => ''],
        ]);
    }

    public function confirm(Request $request, array $params): void
    {
        $order = $this->ownOrder($params['number']);
        if ($order['status'] === 'paid') {
            redirect('/checkout/success/' . $order['number']);
        }
        $gateway = (string) $order['gateway'];
        $meta = PaymentService::GATEWAYS[$gateway] ?? null;
        if (!$meta) {
            abort(404);
        }
        $last4 = null;
        if ($meta['kind'] === 'card') {
            $number = preg_replace('/\D+/', '', $request->string('card_number')) ?? '';
            if (strlen($number) < 12 || strlen($number) > 19) {
                flash('error', 'Enter a test card number. Sandbox accepts 4242 4242 4242 4242.');
                back();
            }
            $last4 = substr($number, -4);
            if ($request->string('card_expiry') === '' || strlen($request->string('card_cvc')) < 3) {
                flash('error', 'Expiry and CVC are required in the sandbox. They are not stored.');
                back();
            }
        }
        PaymentService::markPaid((int) $order['id'], $gateway, 'sandbox-' . $order['number'], $last4, [
            'mode' => 'sandbox',
            'note' => 'Confirmed inside Meridian. Full card numbers are not stored.',
        ]);
        CartService::clear(Auth::id());
        flash('success', 'Payment confirmed in sandbox. You are enrolled.');
        redirect('/checkout/success/' . $order['number']);
    }

    public function success(Request $request, array $params): void
    {
        $order = $this->ownOrder($params['number']);
        view('checkout/success', [
            'title' => 'Enrolled · ' . $order['number'],
            'order' => $order,
            'items' => $this->items((int) $order['id']),
        ]);
    }

    public function returning(Request $request, array $params): void
    {
        $gateway = $params['gateway'];
        $order = PaymentService::verifyReturn($gateway, $request->all() + $_GET);
        if (!$order || (int) $order['user_id'] !== Auth::id()) {
            flash('error', 'That return could not be matched to your order.');
            redirect('/orders');
        }
        if ($order['status'] === 'paid') {
            CartService::clear(Auth::id());
            flash('success', 'Payment confirmed.');
            redirect('/checkout/success/' . $order['number']);
        }
        flash('error', 'The gateway has not confirmed payment yet. You can finish in the sandbox if this is a test.');
        redirect('/checkout/pay/' . $order['number']);
    }

    private function payable(): array
    {
        $enrolled = [];
        foreach (CartService::courses() as $course) {
            if (!enrollment_for(Auth::id(), (int) $course['id'])) {
                $enrolled[] = $course;
            }
        }
        return $enrolled;
    }

    private function ownOrder(string $number): array
    {
        $order = Database::first('SELECT * FROM orders WHERE number = ? AND user_id = ?', [$number, Auth::id()]);
        if (!$order) {
            abort(404);
        }
        return $order;
    }

    private function items(int $orderId): array
    {
        return Database::select(
            'SELECT oi.*, c.title, c.slug FROM order_items oi INNER JOIN courses c ON c.id = oi.course_id WHERE oi.order_id = ?',
            [$orderId]
        );
    }
}
