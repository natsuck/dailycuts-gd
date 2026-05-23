<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSimulationController;
use App\Http\Controllers\AdminSaleBannerController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Webhook\PaymongoWebhookController;

Route::get('/', [UserController::class, 'home'])->name('index');
Route::get('/order-success', fn() => view('order_success'))->name('order.success');
Route::get('/product_details/{id}', [UserController::class, 'productDetails'])->name('product_details');
Route::get('/shop', [UserController::class, 'shop'])->name('shop');
Route::get('/contact_us', [UserController::class, 'contactUs'])->name('contact_us');
Route::post('/contact_us', [UserController::class, 'submitResellerInquiry'])->name('contact_us.submit');

Route::get('/dashboard', [UserController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/cart/{id}', [UserController::class, 'addToCart'])->name('cart.add');
    Route::get('/viewcart', [UserController::class, 'viewCart'])->name('viewcart');
    Route::delete('/cart/{id}', [UserController::class, 'removeCart'])->name('cart.remove');
    Route::patch('/cart/update/{id}', [UserController::class, 'updateCart'])->name('cart.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.placeOrder');
    Route::get('/checkout/success', [CheckoutController::class, 'checkoutSuccess'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'checkoutCancel'])->name('checkout.cancel');
});

Route::post('/paymongo/webhook', [PaymongoWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/add_category', [AdminController::class, 'addCategory'])->name('admin.addcategory');
    Route::post('/add_category', [AdminController::class, 'postAddCategory'])->name('admin.postaddcategory');
    Route::get('/view_category', [AdminController::class, 'viewCategory'])->name('admin.viewcategory');
    Route::delete('/delete_category/{id}', [AdminController::class, 'deleteCategory'])->name('admin.categorydelete');
    Route::get('/update_category/{id}', [AdminController::class, 'updateCategory'])->name('admin.categoryupdate');
    Route::post('/update_category/{id}', [AdminController::class, 'postUpdatecategory'])->name('admin.postupdatecategory');

    Route::get('/add_product', [AdminController::class, 'addProduct'])->name('admin.addproduct');
    Route::post('/add_product', [AdminController::class, 'postAddProduct'])->name('admin.postaddproduct');
    Route::get('/view_product', [AdminController::class, 'viewProduct'])->name('admin.viewproduct');
    Route::delete('/deleteproduct/{id}', [AdminController::class, 'deleteProduct'])->name('admin.deleteproduct');
    Route::get('/updateproduct/{id}', [AdminController::class, 'updateProduct'])->name('admin.updateproduct');
    Route::post('/update_product/{id}', [AdminController::class, 'postUpdateProduct'])->name('admin.postupdateproduct');
    Route::match(['get', 'post'], '/search', [AdminController::class, 'searchProduct'])->name('admin.searchproduct');

    Route::get('/view_orders', [AdminController::class, 'viewOrders'])->name('admin.vieworders');
    Route::patch('/view_orders/{id}/status', [AdminController::class, 'updateStatus'])->name('admin.order.updateStatus');
    Route::delete('/view_orders/{id}', [AdminController::class, 'deleteOrder'])->name('admin.order.delete');

    Route::get('/simulation-dashboard', [AdminSimulationController::class, 'dashboard'])->name('admin.simulation.dashboard');
    Route::get('/simulation-dashboard/forecast', [AdminSimulationController::class, 'forecast'])->name('admin.simulation.forecast');
    Route::get('/simulation-dashboard/reorder', [AdminSimulationController::class, 'reorder'])->name('admin.simulation.reorder');
    Route::get('/simulation-dashboard/spoilage', [AdminSimulationController::class, 'spoilage'])->name('admin.simulation.spoilage');

    Route::resource('/sale-banners', AdminSaleBannerController::class)
        ->names('admin.sale-banners')
        ->except(['show']);
});


require __DIR__.'/auth.php';
