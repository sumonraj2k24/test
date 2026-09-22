<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Services\CartService;

final class CartController
{
    public function index(Request $request, array $params = []): void
    {
        view('cart/index', [
            'title' => 'Cart · ' . setting('site_name', 'Meridian'),
            'courses' => CartService::courses(),
        ]);
    }

    public function add(Request $request, array $params = []): void
    {
        CartService::add($request->int('course_id'));
        $to = $request->string('next', '/cart');
        redirect(str_starts_with($to, '/') ? $to : '/cart');
    }

    public function remove(Request $request, array $params = []): void
    {
        CartService::remove($request->int('course_id'));
        flash('success', 'Removed from cart.');
        redirect('/cart');
    }
}
