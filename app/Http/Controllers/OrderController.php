<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Fruit;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'clientName' => ['required', 'string'],
            'date' => ['required', 'date'],
            'payment' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'integer', 'exists:fruits,id'],
            'items.*.unit' => ['required', 'in:box,kg,bunch'],
            'items.*.qty' => ['required', 'numeric'],
            'items.*.price' => ['required', 'numeric'],
            'items.*.kgPerBox' => ['nullable', 'numeric'],
        ]);

        // create order
        $order = Order::create([
            'client_id' => $data['clientId'] ?? null,
            'client_name' => $data['clientName'],
            'date' => $data['date'],
            'payment' => $data['payment'] ?? null,
            'total' => 0,
            'total_paid' => 0,
        ]);

        $total = 0;
        foreach ($data['items'] as $it) {
            $fruit = Fruit::find($it['productId']);
            $unitPrice = $it['price'];
            $kgPerBox = isset($it['kgPerBox']) && $it['kgPerBox'] > 0 ? $it['kgPerBox'] : null;

            if ($it['unit'] === 'box' || $it['unit'] === 'bunch') {
                $qty = max(0, floor($it['qty']));
                $lineTotal = $qty * $unitPrice;
            } else {
                // kg: price is already per kg
                $qty = $it['qty'];
                $lineTotal = $qty * $unitPrice;
            }

            $total += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'fruit_id' => $fruit ? $fruit->id : null,
                'product_name' => $fruit ? $fruit->name : 'Produto',
                'unit' => $it['unit'],
                'price' => $unitPrice,
                'kg_per_box' => $kgPerBox,
                'qty' => $qty,
                'total' => $lineTotal,
            ]);
        }

        $order->update(['total' => $total]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'order_id' => $order->id]);
        }

        // Redirect to the invoice/show page for the created order. Inertia will follow this redirect.
        return redirect()->route('pedidos.show', $order->id);
    }

    /**
     * Return orders for listing (JSON)
     */
    public function index(Request $request)
    {
        $orders = Order::withCount('items')->orderBy('created_at', 'desc')->get();

        $result = $orders->map(function ($o) {
            $dateStr = null;
            if ($o->date) {
                if (is_string($o->date)) {
                    $dateStr = $o->date;
                } elseif (method_exists($o->date, 'format')) {
                    $dateStr = $o->date->format('Y-m-d');
                }
            }
            $dateStr = $dateStr ?: ($o->created_at ? $o->created_at->format('Y-m-d') : null);

            $totalPaid = (float) ($o->total_paid ?? 0);
            $total = (float) $o->total;
            $remaining = (float) $o->remaining_balance;
            $percentage = (float) $o->payment_percentage;
            $status = $o->payment_status;

            return [
                'id' => $o->id,
                'cliente' => $o->client_name,
                'total' => $total,
                'total_paid' => $totalPaid,
                'remaining' => $remaining,
                'payment_percentage' => $percentage,
                'payment_status' => $status,
                'data' => $dateStr,
                'itens' => $o->items_count ?? 0,
            ];
        });

        return response()->json($result);
    }

    /**
     * Show single order (invoice) page
     */
    public function show($id)
    {
        $order = Order::with(['items', 'client'])->findOrFail($id);

        // prepare data for front-end
        $items = $order->items->map(function ($it) {
            return [
                'id' => $it->id,
                'product_name' => $it->product_name,
                'unit' => $it->unit,
                'price' => (float) $it->price,
                'kg_per_box' => $it->kg_per_box !== null ? (float) $it->kg_per_box : null,
                'qty' => (float) $it->qty,
                'total' => (float) $it->total,
            ];
        });

        $client = $order->client;

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'client_name' => $order->client_name,
                'date' => $order->date ? (string) $order->date : ($order->created_at ? $order->created_at->toDateString() : null),
                'payment' => $order->payment,
                'total' => (float) $order->total,
            ],
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'address' => $client->address,
            ] : null,
            'items' => $items,
        ]);
    }

    /**
     * Delete an order
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete(); // cascade will delete items

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('dashboard')->with('message', 'Pedido deletado com sucesso.');
    }

    /**
     * Return dashboard statistics (JSON)
     */
    public function stats(): \Illuminate\Http\JsonResponse
    {
        // Pedidos nas últimas 24 horas
        $pedidosHoje = Order::where('created_at', '>=', now()->subDay())
            ->count();

        // Faturamento estimado (soma dos totais dos pedidos de hoje)
        $faturamento = Order::where('created_at', '>=', now()->subDay())
            ->sum('total');

        // Clientes ativos (clientes únicos que fizeram pedidos este mês)
        $clientesAtivos = Order::where('created_at', '>=', now()->startOfMonth())
            ->distinct('client_name')
            ->count('client_name');

        return response()->json([
            'pedidos' => $pedidosHoje,
            'faturamento' => (float) $faturamento,
            'clientes' => $clientesAtivos,
        ]);
    }

    /**
     * Show edit order page
     */
    public function edit($id)
    {
        $order = Order::with(['items', 'client'])->findOrFail($id);
        $fruits = Fruit::orderBy('name')->get();

        // Prepare items for front-end
        $items = $order->items->map(function ($it) {
            return [
                'id' => $it->id,
                'productId' => $it->fruit_id ?? 0,
                'product_name' => $it->product_name,
                'unit' => $it->unit,
                'price' => (float) $it->price,
                'kgPerBox' => $it->kg_per_box !== null ? (float) $it->kg_per_box : 20,
                'qty' => (float) $it->qty,
                'total' => (float) $it->total,
            ];
        });

        return Inertia::render('Orders/Edit', [
            'order' => [
                'id' => $order->id,
                'client_name' => $order->client_name,
                'date' => $order->date ? (string) $order->date : ($order->created_at ? $order->created_at->toDateString() : null),
                'payment' => $order->payment,
                'total' => (float) $order->total,
            ],
            'items' => $items,
            'fruits' => $fruits,
        ]);
    }

    /**
     * Update an order
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'clientId' => ['nullable', 'integer', 'exists:clients,id'],
            'clientName' => ['required', 'string'],
            'date' => ['required', 'date'],
            'payment' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'integer', 'exists:fruits,id'],
            'items.*.unit' => ['required', 'in:box,kg,bunch'],
            'items.*.qty' => ['required', 'numeric'],
            'items.*.price' => ['required', 'numeric'],
            'items.*.kgPerBox' => ['nullable', 'numeric'],
        ]);

        // Update order
        $order->update([
            'client_id' => $data['clientId'] ?? null,
            'client_name' => $data['clientName'],
            'date' => $data['date'],
            'payment' => $data['payment'] ?? null,
        ]);

        // Delete existing items
        $order->items()->delete();

        // Create new items
        $total = 0;
        foreach ($data['items'] as $it) {
            $fruit = Fruit::find($it['productId']);
            $unitPrice = $it['price'];
            $kgPerBox = isset($it['kgPerBox']) && $it['kgPerBox'] > 0 ? $it['kgPerBox'] : null;

            if ($it['unit'] === 'box' || $it['unit'] === 'bunch') {
                $qty = max(0, floor($it['qty']));
                $lineTotal = $qty * $unitPrice;
            } else {
                // kg: price is already per kg
                $qty = $it['qty'];
                $lineTotal = $qty * $unitPrice;
            }

            $total += $lineTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'fruit_id' => $fruit ? $fruit->id : null,
                'product_name' => $fruit ? $fruit->name : 'Produto',
                'unit' => $it['unit'],
                'price' => $unitPrice,
                'kg_per_box' => $kgPerBox,
                'qty' => $qty,
                'total' => $lineTotal,
            ]);
        }

        $order->update(['total' => $total]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'order_id' => $order->id]);
        }

        // Redirect to the invoice/show page for the updated order
        return redirect()->route('pedidos.show', $order->id);
    }
}
