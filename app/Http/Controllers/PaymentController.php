<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Show payment management page for an order
     */
    public function show(Order $order)
    {
        $order->load(['payments', 'client', 'items']);

        $payments = $order->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'balance_after' => (float) $payment->balance_after,
                'notes' => $payment->notes,
                'created_at' => $payment->created_at->format('d/m/Y H:i'),
            ];
        });

        return Inertia::render('Orders/Payments', [
            'order' => [
                'id' => $order->id,
                'client_name' => $order->client_name,
                'date' => $order->date ? (string) $order->date : ($order->created_at ? $order->created_at->toDateString() : null),
                'total' => (float) $order->total,
                'total_paid' => (float) ($order->total_paid ?? 0),
                'remaining_balance' => (float) $order->remaining_balance,
                'payment_percentage' => (float) $order->payment_percentage,
                'payment_status' => $order->payment_status,
            ],
            'payments' => $payments,
        ]);
    }

    /**
     * Store a new payment
     */
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = (float) $data['amount'];
        $currentPaid = (float) ($order->total_paid ?? 0);
        $orderTotal = (float) $order->total;
        $newTotalPaid = $currentPaid + $amount;

        // Não permite pagar mais que o total
        if ($newTotalPaid > $orderTotal) {
            return back()->withErrors([
                'amount' => 'O valor do pagamento não pode exceder o total do pedido. Saldo restante: R$ ' . number_format($orderTotal - $currentPaid, 2, ',', '.'),
            ]);
        }

        $balanceAfter = $orderTotal - $newTotalPaid;

        // Create payment
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'notes' => $data['notes'] ?? null,
        ]);

        // Update order total_paid
        $order->update(['total_paid' => $newTotalPaid]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'payment' => $payment,
                'order' => [
                    'total_paid' => $newTotalPaid,
                    'remaining_balance' => $balanceAfter,
                    'payment_percentage' => ($newTotalPaid / $orderTotal) * 100,
                    'payment_status' => $balanceAfter <= 0 ? 'paid' : ($newTotalPaid > 0 ? 'partial' : 'unpaid'),
                ],
            ]);
        }

        return redirect()->route('orders.payments.show', $order->id)
            ->with('success', 'Pagamento registrado com sucesso!');
    }

    /**
     * Delete a payment
     */
    public function destroy(Order $order, Payment $payment)
    {
        // Recalculate total_paid
        $currentPaid = (float) ($order->total_paid ?? 0);
        $newTotalPaid = max(0, $currentPaid - (float) $payment->amount);

        // Delete payment
        $payment->delete();

        // Update order total_paid
        $order->update(['total_paid' => $newTotalPaid]);

        // Recalculate balance_after for remaining payments
        $remainingPayments = $order->payments()->orderBy('created_at', 'asc')->get();
        $runningTotal = 0;
        foreach ($remainingPayments as $p) {
            $runningTotal += (float) $p->amount;
            $p->update(['balance_after' => (float) $order->total - $runningTotal]);
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('orders.payments.show', $order->id)
            ->with('success', 'Pagamento removido com sucesso!');
    }
}
