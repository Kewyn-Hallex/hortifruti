<?php

namespace App\Http\Controllers;

use App\Models\Fruit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FruitController extends Controller
{
    /**
     * Show fruits for Company page
     */
    public function index()
    {
        $fruits = Fruit::orderBy('name')->get()->map(function ($fruit) {
            return [
                'id' => $fruit->id,
                'name' => $fruit->name,
                'price' => (float) $fruit->price,
                'price_box' => (float) $fruit->price_box,
                'price_kg' => (float) $fruit->price_kg,
                'price_bunch' => (float) $fruit->price_bunch,
                'updated_at' => $fruit->updated_at ? $fruit->updated_at->toIso8601String() : null,
            ];
        });

        return Inertia::render('Company', [
            'fruits' => $fruits,
        ]);
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        return Inertia::render('NewProduct');
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:fruits,name'],
        ]);

        Fruit::create([
            'name' => $data['name'],
            'price' => 0,
            'price_box' => 0,
            'price_kg' => 0,
            'price_bunch' => 0,
        ]);

        return redirect()->route('products.create')->with('success', 'Produto cadastrado com sucesso!');
    }

    /**
     * Update fruit price (price of the day)
     */
    public function update(Request $request, Fruit $fruit)
    {
        $data = $request->validate([
            'price' => ['required', 'numeric'],
        ]);

        $fruit->update(['price' => $data['price']]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'fruit' => $fruit]);
        }

        return redirect()->back();
    }

    /**
     * Bulk update prices. Expects `prices` => [ { id, price_box, price_kg, price_bunch }, ... ]
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*.id' => ['required', 'integer', 'exists:fruits,id'],
            'prices.*.price_box' => ['nullable', 'numeric', 'min:0'],
            'prices.*.price_kg' => ['nullable', 'numeric', 'min:0'],
            'prices.*.price_bunch' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['prices'] as $p) {
            $updateData = [];
            if (isset($p['price_box'])) {
                $updateData['price_box'] = $p['price_box'];
            }
            if (isset($p['price_kg'])) {
                $updateData['price_kg'] = $p['price_kg'];
            }
            if (isset($p['price_bunch'])) {
                $updateData['price_bunch'] = $p['price_bunch'];
            }
            if (! empty($updateData)) {
                Fruit::where('id', $p['id'])->update($updateData);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('company');
    }

    /**
     * Delete a product
     */
    public function destroy(Fruit $fruit)
    {
        $fruit->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('company')->with('success', 'Produto excluído com sucesso!');
    }
}
