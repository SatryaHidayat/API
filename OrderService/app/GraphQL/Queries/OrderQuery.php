<?php

namespace App\GraphQL\Queries;

use App\Models\Order;

class OrderQuery
{
    public function all(): array
    {
        return Order::with('items')->latest()->get()->all();
    }

    public function find($_, array $args): array
    {
        $order = Order::with('items')->find($args['id']);

        if (! $order) {
            return [
                'status' => 'Failed',
                'message' => 'Order not found',
                'data' => null,
            ];
        }

        return [
            'status' => 'Success',
            'message' => 'Order found',
            'data' => $order,
        ];
    }

    public function byUser($_, array $args): array
    {
        $orders = Order::with('items')
            ->where('user_id', $args['user_id'])
            ->latest()
            ->get();

        return [
            'status' => 'Success',
            'message' => 'List of orders by user',
            'data' => $orders,
        ];
    }
}
