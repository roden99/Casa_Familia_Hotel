<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\SalesAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseItemController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\ProductUnitController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\StrengthController;
use App\Http\Controllers\DrugFormController;
use App\Http\Controllers\StoreInventoryController;
use App\Http\Controllers\TransferStockController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ExpirationController;

Route::get('/login', function () {
    return Inertia::render('Login/Index');
})->name('login');

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('dashboard/chart-data', [DashboardController::class, 'chartData'])->middleware(['auth'])->name('dashboard.chart-data');

Route::get('under-construction', function () {
    return Inertia::render('UnderConstruction');
})->middleware(['auth', 'verified'])->name('under-construction');

Route::resource('suppliers', SupplierController::class);

Route::resource('customers', CustomerController::class);
Route::get('customer-accounts', [CustomerAccountController::class, 'index'])->name('customer-accounts.index');
Route::post('customer-accounts', [CustomerAccountController::class, 'store'])->name('customer-accounts.store');
Route::get('customer-accounts/{id}/ledger', [CustomerAccountController::class, 'ledger'])->name('customer-accounts.ledger');
Route::get('customer-accounts/{id}/unpaid-orders', [CustomerAccountController::class, 'unpaidOrders'])->name('customer-accounts.unpaid-orders');
Route::get('customer-accounts/{id}/orders-for-payment/{paymentId}', [CustomerAccountController::class, 'ordersForPayment'])->name('customer-accounts.orders-for-payment');
Route::post('customer-accounts/{id}/payments', [CustomerAccountController::class, 'storePayment'])->name('customer-accounts.payments.store');
Route::patch('customer-accounts/{id}/forward-balance', [CustomerAccountController::class, 'setForwardBalance'])->name('customer-accounts.forward-balance');
Route::post('customer-accounts/{id}/invoices', [CustomerAccountController::class, 'storeInvoice'])->name('customer-accounts.invoices.store');
Route::patch('customer-accounts/{csaId}/invoices/{invoiceId}', [CustomerAccountController::class, 'updateInvoice'])->name('customer-accounts.invoices.update');
Route::patch('customer-accounts/{csaId}/payments/{paymentId}', [CustomerAccountController::class, 'updatePayment'])->name('customer-accounts.payments.update');
Route::delete('customer-accounts/{csaId}/invoices/{invoiceId}', [CustomerAccountController::class, 'destroyInvoice'])->name('customer-accounts.invoices.destroy');
Route::delete('customer-accounts/{csaId}/payments/{paymentId}', [CustomerAccountController::class, 'destroyPayment'])->name('customer-accounts.payments.destroy');
Route::post('customer-accounts/invoices/{invoiceId}/payments', [CustomerAccountController::class, 'storeInvoicePayment'])->name('customer-accounts.invoices.payments.store');
Route::patch('customer-accounts/invoices/{invoiceId}/payments/{paymentId}', [CustomerAccountController::class, 'updateInvoicePayment'])->name('customer-accounts.invoices.payments.update');
Route::delete('customer-accounts/invoices/{invoiceId}/payments/{paymentId}', [CustomerAccountController::class, 'destroyInvoicePayment'])->name('customer-accounts.invoices.payments.destroy');
Route::resource('sales-accounts', SalesAccountController::class);

Route::resource('brands', BrandController::class);
Route::resource('strengths', StrengthController::class);
Route::resource('drugforms', DrugFormController::class);
Route::resource('product-units', ProductUnitController::class);
Route::resource('product-types', ProductTypeController::class);
Route::resource('products', ProductController::class);
Route::patch('products/{product}/initial-inventory', [ProductController::class, 'initialInventory'])->name('products.initialInventory');
Route::patch('products/{product}/reorder-level', [ProductController::class, 'reorderLevel'])->name('products.reorderLevel');
Route::get('products/{product}/history', [ProductController::class, 'history'])->name('products.history');
Route::post('products/{product}/lots', [ProductController::class, 'storeLot'])->name('products.lots.store');
Route::get('products/{product}/lots', [ProductController::class, 'getLots'])->name('products.lots.index');
Route::delete('products/{product}/lots/{lot}', [ProductController::class, 'destroyLot'])->name('products.lots.destroy');
Route::resource('warehouses', WarehouseController::class);
Route::resource('warehouse-items', WarehouseItemController::class);
Route::resource('deliveries', DeliveryController::class);
Route::resource('sales-orders', SalesOrderController::class);
Route::resource('sales-accounts', SalesAccountController::class);

Route::get('store-inventory', [StoreInventoryController::class, 'index'])->name('store-inventory.index');
Route::patch('store-inventory/{product}/pos-qty', [StoreInventoryController::class, 'updatePosQty'])->name('store-inventory.updatePosQty');
Route::get('store-inventory/{product}/history', [StoreInventoryController::class, 'history'])->name('store-inventory.history');

Route::get('expirations', [ExpirationController::class, 'index'])->name('expirations.index');

Route::get('products/{product}/multiplier', [ProductController::class, 'multiplier'])->name('products.multiplier');
Route::resource('transfer-stocks', TransferStockController::class)->only(['index', 'store', 'show', 'destroy']);

Route::get('pos-products', [StoreInventoryController::class, 'posProducts'])->name('pos-products.index');
Route::resource('pos', PosController::class)->only(['index', 'store', 'show', 'destroy']);





require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';





Route::get('/avatars/shadcn.jpg', function () {
    $path = public_path('avatars/shadcn.jpg');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});
