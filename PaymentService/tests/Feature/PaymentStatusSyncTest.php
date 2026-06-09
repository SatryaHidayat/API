<?php

namespace Tests\Feature;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_payment_updates_the_related_order(): void
    {
        Http::fake([
            '*/api/orders/15' => Http::response([
                'status' => 'Success',
                'message' => 'Status order berhasil diupdate',
            ]),
        ]);

        $payment = Payment::create([
            'order_id' => 15,
            'user_id' => 3,
            'amount' => 125000,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->patchJson('/api/payments/' . $payment->id . '/status', [
            'status' => 'paid',
        ])->assertOk()
            ->assertJsonPath('data.status', 'paid');

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/api/orders/15')
            && $request['status'] === 'paid');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_payment_stays_pending_when_order_update_fails(): void
    {
        Http::fake([
            '*/api/orders/15' => Http::response(['message' => 'Order not found'], 404),
        ]);

        $payment = Payment::create([
            'order_id' => 15,
            'user_id' => 3,
            'amount' => 125000,
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $this->patchJson('/api/payments/' . $payment->id . '/status', [
            'status' => 'paid',
        ])->assertStatus(502);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }
}
