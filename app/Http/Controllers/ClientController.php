<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function create()
    {
        return Inertia::render('RegisterClient');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
        ]);

        $client = Client::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'client' => $client]);
        }

        return redirect()->route('clients.create')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $clients = Client::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'phone' => $client->phone,
                    'address' => $client->address,
                ];
            });

        return response()->json($clients);
    }
}
