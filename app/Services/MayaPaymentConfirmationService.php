<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Confirms a Maya payment against an order and marks it paid.
 *
 * Shared by the Maya webhook handler and the checkout success page
 * reconciliation so both code paths use identical, idempotent logic. Accepts
 * both documented payload shapes:
 *   - GET Payment shape: id, status, isPaid, amount, currency, fundSource
 *   - Get Checkout shape: id, status (COMPLETED/EXPIRED), paymentStatus,
 *     totalAmount.value, totalAmount.currency, paymentScheme
 */
class MayaPaymentConfirmationService
{
    /**
     * Attempts to mark the order paid from a PAYMENT_SUCCESS payload.
     *
     * Returns the order id when the order was newly confirmed (so the caller
     * knows to trigger delivery dispatch), or null when nothing changed
     * (already paid, cancelled, mismatched amount/currency, or not a success).
     */
    public function confirm(Order $order, array $payload): ?int
    {
        $status = strtoupper((string) ($payload['paymentStatus'] ?? $payload['status'] ?? ''));

        if ($status !== 'PAYMENT_SUCCESS') {
            return null;
        }

        $paymentId = (string) ($payload['id'] ?? '');
        $isPaid = (bool) ($payload['isPaid'] ?? true);
        $paidAmount = $this->amountFrom($payload);
        $paidCurrency = strtoupper((string) ($payload['currency'] ?? data_get($payload, 'totalAmount.currency') ?? ''));
        $fundSourceType = is_array($payload['fundSource'] ?? null)
            ? (string) ($payload['fundSource']['type'] ?? '')
            : (string) ($payload['paymentScheme'] ?? '');

        $confirmedOrderId = null;

        DB::transaction(function () use ($order, $isPaid, $paymentId, $paidAmount, $paidCurrency, $fundSourceType, &$confirmedOrderId) {
            $lockedOrder = Order::with('items')->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->payment_status === 'paid') {
                return;
            }

            if ($lockedOrder->status === 'cancelled') {
                Log::warning('Ignored payment confirmation for a cancelled order.', [
                    'order_id' => $lockedOrder->id,
                ]);

                return;
            }

            $expectedAmount = round((float) $lockedOrder->total, 2);
            $paidAmount = round((float) $paidAmount, 2);

            if (! $isPaid || $paidAmount === 0.0 || $paidAmount !== $expectedAmount || $paidCurrency !== 'PHP') {
                Log::warning('Rejected payment confirmation due to an amount/currency mismatch.', [
                    'order_id' => $lockedOrder->id,
                    'order_total' => $lockedOrder->total,
                    'paid_amount' => $paidAmount,
                    'paid_currency' => $paidCurrency,
                ]);

                return;
            }

            $lockedOrder->payment_status = 'paid';
            $lockedOrder->payment_method = $this->paymentMethodFor($fundSourceType);
            $lockedOrder->payment_intent_id = $paymentId !== '' ? $paymentId : null;
            $lockedOrder->save();

            Cart::where('user_id', $lockedOrder->user_id)->delete();

            $confirmedOrderId = $lockedOrder->id;
        });

        return $confirmedOrderId;
    }

    protected function amountFrom(array $payload): string
    {
        if (isset($payload['amount']) && $payload['amount'] !== '') {
            return (string) $payload['amount'];
        }

        // Get Checkout shapes use totalAmount.value; the failure/expired webhook
        // samples use totalAmount.amount. Accept both.
        $value = data_get($payload, 'totalAmount.value') ?? data_get($payload, 'totalAmount.amount');

        return $value === null ? '' : (string) $value;
    }

    /**
     * Maps the payment source (fundSource.type or the Get Checkout
     * paymentScheme) to the order's stored payment_method label.
     */
    protected function paymentMethodFor(string $source): string
    {
        $source = strtolower(trim($source));

        if (in_array($source, ['visa', 'mastercard', 'master-card', 'jcb', 'amex', 'unionpay', 'card'], true)) {
            return 'card';
        }

        return match ($source) {
            'paymaya', 'maya-wallet', 'maya-credit' => 'maya',
            'qrph' => 'qrph',
            default => 'unknown',
        };
    }
}
