<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\FruitController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Models\Fruit;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('company', [FruitController::class, 'index'])->middleware(['auth', 'verified'])->name('company');

// Rotas para cadastro de produtos
Route::get('products/new', [FruitController::class, 'create'])->middleware(['auth', 'verified'])->name('products.create');
Route::post('products', [FruitController::class, 'store'])->middleware(['auth', 'verified'])->name('products.store');
Route::delete('products/{fruit}', [FruitController::class, 'destroy'])->middleware(['auth', 'verified'])->name('products.destroy');

// Rota para criar novo pedido (tela de criação de pedidos) - passa os preços atuais das frutas
Route::get('pedidos/create', function () {
    $fruits = Fruit::orderBy('name')->get();

    return Inertia::render('Orders/Create', [
        'fruits' => $fruits,
    ]);
})->middleware(['auth', 'verified'])->name('pedidos.create');

// Endpoint to store created orders
Route::post('pedidos', [OrderController::class, 'store'])->middleware(['auth', 'verified'])->name('pedidos.store');
// Show single order (invoice)
Route::get('pedidos/{order}', [OrderController::class, 'show'])->middleware(['auth', 'verified'])->name('pedidos.show');
// Delete order
Route::delete('pedidos/{order}', [OrderController::class, 'destroy'])->middleware(['auth', 'verified'])->name('pedidos.destroy');
// API endpoint to fetch orders for dashboard listing
Route::get('api/orders', [OrderController::class, 'index'])->middleware(['auth', 'verified'])->name('api.orders.index');
// API endpoint to fetch dashboard statistics
Route::get('api/stats', [OrderController::class, 'stats'])->middleware(['auth', 'verified'])->name('api.stats');
// Edit order routes
Route::get('pedidos/{order}/edit', [OrderController::class, 'edit'])->middleware(['auth', 'verified'])->name('pedidos.edit');
Route::put('pedidos/{order}', [OrderController::class, 'update'])->middleware(['auth', 'verified'])->name('pedidos.update');

// Endpoint para atualizar preço da fruta (single)
Route::put('fruits/{fruit}', [FruitController::class, 'update'])->middleware(['auth', 'verified'])->name('fruits.update');

// Endpoint para atualizar preços em lote (usado por "Salvar Preços")
Route::post('fruits/bulk-update', [FruitController::class, 'bulkUpdate'])->middleware(['auth', 'verified'])->name('fruits.bulkUpdate');

// Simple API endpoint to get a fruit as JSON (used by client to fetch latest price)
Route::get('api/fruits/{fruit}', function (Fruit $fruit) {
    return response()->json($fruit);
})->middleware(['auth', 'verified'])->name('api.fruits.show');

// Rotas para cadastro de clientes
Route::get('clients/create', [ClientController::class, 'create'])->middleware(['auth', 'verified'])->name('clients.create');
Route::post('clients', [ClientController::class, 'store'])->middleware(['auth', 'verified'])->name('clients.store');
// API endpoint para buscar clientes (autocomplete)
Route::get('api/clients/search', [ClientController::class, 'search'])->middleware(['auth', 'verified'])->name('api.clients.search');

// Rotas para pagamentos
Route::get('pedidos/{order}/payments', [PaymentController::class, 'show'])->middleware(['auth', 'verified'])->name('orders.payments.show');
Route::post('pedidos/{order}/payments', [PaymentController::class, 'store'])->middleware(['auth', 'verified'])->name('orders.payments.store');
Route::delete('pedidos/{order}/payments/{payment}', [PaymentController::class, 'destroy'])->middleware(['auth', 'verified'])->name('orders.payments.destroy');

require __DIR__.'/settings.php';
