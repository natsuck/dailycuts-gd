<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminHeroSlideController;
use App\Http\Controllers\AdminSaleBannerController;
use App\Http\Controllers\AdminSimulationController;
use App\Http\Controllers\AdminStoreLocationController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Webhook\LalamoveWebhookController;
use App\Http\Controllers\Webhook\MayaWebhookController;
use App\Http\Controllers\WishlistController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [UserController::class, 'home'])->name('index');
Route::get('/order-success', [PageController::class, 'orderSuccess'])->name('order.success');
Route::get('/product_details/{id}', [UserController::class, 'productDetails'])->name('product_details');
Route::get('/shop', [UserController::class, 'shop'])->name('shop');
Route::get('/contact_us', [UserController::class, 'contactUs'])->name('contact_us');
Route::get('/find-our-stores', [UserController::class, 'storeLocations'])->name('store.locations');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('/terms-and-conditions', [PageController::class, 'termsAndConditions'])->name('terms.conditions');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/store-policies', [PageController::class, 'storePolicies'])->name('store.policies');
Route::post('/contact_us', [UserController::class, 'submitResellerInquiry'])
    ->middleware('throttle:10,1')
    ->name('contact_us.submit');

Route::post('/newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:5,1')
    ->name('newsletter.subscribe');

Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe');

Route::get('/dashboard', [UserController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::post('/cart/{id}', [UserController::class, 'addToCart'])
        ->middleware('throttle:60,1')
        ->name('cart.add');
    Route::get('/viewcart', [UserController::class, 'viewCart'])->name('viewcart');
    Route::delete('/cart/{id}', [UserController::class, 'removeCart'])->name('cart.remove');
    Route::patch('/cart/update/{id}', [UserController::class, 'updateCart'])->name('cart.update');
    Route::patch('/cart/{id}/variant', [UserController::class, 'changeVariant'])->name('cart.changeVariant');
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])
        ->middleware('throttle:10,1')
        ->name('checkout.placeOrder');
    Route::get('/checkout/success', [CheckoutController::class, 'checkoutSuccess'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'checkoutCancel'])->name('checkout.cancel');
    Route::get('/checkout/failure', [CheckoutController::class, 'checkoutFailure'])->name('checkout.failure');
    Route::get('/checkout/estimate-shipping', [CheckoutController::class, 'estimateShipping'])
        ->middleware('throttle:30,1')
        ->name('checkout.estimateShipping');
    Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon');
    Route::delete('/checkout/coupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.couponRemove');
});

Route::get('/maya/webhook', [PageController::class, 'mayaWebhook']);

Route::post('/maya/webhook', [MayaWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/lalamove/webhook', [LalamoveWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:10,1')
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:5,1')
        ->name('profile.destroy');
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
    Route::post('/view_orders/{id}/dispatch-lalamove', [AdminController::class, 'dispatchToLalamove'])->name('admin.order.dispatchLalamove');
    Route::delete('/view_orders/{id}', [AdminController::class, 'deleteOrder'])->name('admin.order.delete');

    Route::get('/simulation-dashboard', [AdminSimulationController::class, 'dashboard'])->name('admin.simulation.dashboard');
    Route::get('/simulation-dashboard/forecast', [AdminSimulationController::class, 'forecast'])->name('admin.simulation.forecast');
    Route::get('/simulation-dashboard/reorder', [AdminSimulationController::class, 'reorder'])->name('admin.simulation.reorder');
    Route::get('/simulation-dashboard/spoilage', [AdminSimulationController::class, 'spoilage'])->name('admin.simulation.spoilage');

    Route::resource('/sale-banners', AdminSaleBannerController::class)
        ->names('admin.sale-banners')
        ->except(['show']);

    Route::resource('/hero-slides', AdminHeroSlideController::class)
        ->names('admin.hero-slides')
        ->except(['show']);

    Route::resource('/store-locations', AdminStoreLocationController::class)
        ->names('admin.store-locations')
        ->except(['show']);
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/toggle/{id}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{id}/lalamove-status', [OrderController::class, 'lalamoveStatus'])->name('orders.lalamoveStatus');
});

Route::middleware(['auth', 'verified', 'customer'])->group(function () {
    Route::post('/products/{id}/reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('reviews.store');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('reviews.destroy');
});

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::resource('admin/coupons', CouponController::class)
            ->names('admin.coupons')
            ->middleware('throttle:30,1');

    Route::prefix('admin/inventory')->name('admin.inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/{id}/adjust', [InventoryController::class, 'adjust'])->name('adjust');
        Route::get('/history/{id}', [InventoryController::class, 'history'])->name('history');
        Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('lowStock');
    });

    Route::prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'salesByDate'])->name('sales');
        Route::get('/export/csv', [ReportController::class, 'exportSalesCsv'])->name('exportCsv');
    });
});

require __DIR__.'/auth.php';
