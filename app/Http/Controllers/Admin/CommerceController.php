<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Services\NotificationService;
use App\Services\PaymentService;

final class CommerceController
{
    public function orders(Request $request, array $params = []): void
    {
        $status = (string) $request->query('status', '');
        $sql = 'SELECT o.*, u.name, u.email FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE 1 = 1';
        $bind = [];
        if ($status !== '') {
            $sql .= ' AND o.status = ?';
            $bind[] = $status;
        }
        $sql .= ' ORDER BY datetime(o.created_at) DESC LIMIT 200';
        view('admin/orders', [
            'title' => 'Orders',
            'active' => 'orders',
            'orders' => Database::select($sql, $bind),
            'status' => $status,
        ], 'layouts/dashboard');
    }

    public function export(Request $request, array $params = []): void
    {
        $rows = Database::select(
            'SELECT o.number, o.status, o.gateway, o.total_cents, o.currency, o.created_at, o.paid_at, u.email
             FROM orders o INNER JOIN users u ON u.id = o.user_id ORDER BY o.id'
        );
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="meridian-orders.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['number', 'status', 'gateway', 'total', 'currency', 'created_at', 'paid_at', 'email']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['number'], $row['status'], $row['gateway'], number_format($row['total_cents'] / 100, 2, '.', ''),
                $row['currency'], $row['created_at'], $row['paid_at'], $row['email'],
            ]);
        }
        fclose($out);
    }

    public function refund(Request $request, array $params): void
    {
        try {
            PaymentService::refund((int) $params['id'], Auth::id());
            flash('success', 'Order refunded and access withdrawn.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/orders');
    }

    public function payouts(Request $request, array $params = []): void
    {
        view('admin/payouts', [
            'title' => 'Payouts',
            'active' => 'payouts',
            'payouts' => Database::select(
                'SELECT p.*, u.name, u.email FROM payouts p INNER JOIN users u ON u.id = p.instructor_id ORDER BY CASE p.status WHEN "requested" THEN 0 WHEN "approved" THEN 1 ELSE 2 END, datetime(p.requested_at) DESC'
            ),
        ], 'layouts/dashboard');
    }

    public function updatePayout(Request $request, array $params): void
    {
        $payout = Database::first('SELECT * FROM payouts WHERE id = ?', [(int) $params['id']]);
        if (!$payout) {
            abort(404);
        }
        $status = $request->string('status');
        if (!in_array($status, ['requested', 'approved', 'paid', 'rejected'], true)) {
            abort(400);
        }
        Database::update('payouts', [
            'status' => $status,
            'note' => $request->string('note') ?: $payout['note'],
            'processed_at' => in_array($status, ['paid', 'rejected'], true) ? now() : $payout['processed_at'],
            'processed_by' => Auth::id(),
        ], 'id = ?', [(int) $payout['id']]);
        NotificationService::push(
            (int) $payout['instructor_id'],
            'payout',
            'Payout ' . $status,
            money((int) $payout['amount_cents']) . ' is now ' . $status . '.',
            '/studio/earnings'
        );
        audit('payout.' . $status, 'payout:' . $payout['id']);
        flash('success', 'Payout marked ' . $status . '.');
        redirect('/admin/payouts');
    }
}
