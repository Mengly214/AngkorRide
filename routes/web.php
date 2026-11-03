<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Customer\VehicleController as CustomerVehicleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\AuthController as PortalAuthController;
use App\Http\Controllers\Portal\AvailabilityController as PortalAvailabilityController;
use App\Http\Controllers\Portal\BookingController as PortalBookingController;
use App\Http\Controllers\Portal\CustomerController as PortalCustomerController;
use App\Http\Controllers\Portal\EarningsController as PortalEarningsController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\Portal\RegisterController as PortalRegisterController;
use App\Http\Controllers\Portal\SettingsController as PortalSettingsController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\VehicleController as PortalVehicleController;
use App\Http\Controllers\Portal\VehiclePhotoController as PortalVehiclePhotoController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Portal\VoucherController as PortalVoucherController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureBusinessOwner;
use App\Http\Middleware\EnsureCustomer;

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/explore', [HomeController::class, 'explore'])
    ->name('explore');

Route::get('/partners', [BusinessController::class, 'index'])
    ->name('partners.index');

Route::get('/business/{business}', [BusinessController::class, 'show'])
    ->name('business.show');

/*
|--------------------------------------------------------------------------
| Customer booking (browse freely; account needed to book / see bookings)
|--------------------------------------------------------------------------
*/
Route::get('/business/{business}/vehicles/{vehicle}', [CustomerVehicleController::class, 'show'])
    ->name('vehicles.show');
Route::get('/business/{business}/vehicles/{vehicle}/quote', [CustomerVehicleController::class, 'quote'])
    ->middleware('throttle:60,1')
    ->name('vehicles.quote');
Route::get('/business/{business}/vehicles/{vehicle}/checkout', [BookingController::class, 'checkout'])
    ->name('bookings.checkout');
Route::post('/business/{business}/vehicles/{vehicle}/book', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('bookings.store');

// Customer accounts (browsing never needs one; booking and My Bookings do)
Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:10,1')->name('register.submit');
Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

// My Bookings: signed-in customers only, and only their own bookings
Route::middleware(['auth', EnsureCustomer::class])->group(function () {
    Route::post('/business/{business}/vehicles/{vehicle}/favorite', [CustomerVehicleController::class, 'favorite'])
        ->middleware('throttle:30,1')
        ->name('vehicles.favorite');
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/my-bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/my-bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->middleware('throttle:10,1')
        ->name('bookings.cancel');

    // Optional online payment for a confirmed booking
    Route::post('/my-bookings/{booking}/pay', [PaymentController::class, 'start'])
        ->middleware('throttle:10,1')
        ->name('payments.start');
    Route::get('/pay/return/{payment}', [PaymentController::class, 'back'])->name('payments.back');
    Route::get('/pay/test/{payment}', [PaymentController::class, 'testShow'])->name('payments.test.show');
    Route::post('/pay/test/{payment}/{result}', [PaymentController::class, 'testAnswer'])
        ->where('result', 'paid|failed')
        ->middleware('throttle:20,1')
        ->name('payments.test.answer');
});

// Payment providers call this from their own servers (no login, no CSRF; checked by signature)
Route::post('/payments/webhook/{driver}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('payments.webhook');

/*
|--------------------------------------------------------------------------
| Business Portal (business_admin dashboard)
|--------------------------------------------------------------------------
*/
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');

    Route::get('/register', [PortalRegisterController::class, 'show'])->name('register');
    Route::post('/register', [PortalRegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.submit');

    Route::middleware(EnsureBusinessOwner::class)->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/bookings', [PortalBookingController::class, 'index'])->name('bookings.index');
        Route::post('/bookings/{booking}/{action}', [PortalBookingController::class, 'act'])
            ->where('action', 'confirm|reject|cancel|pickup|return|paid')
            ->name('bookings.action');

        Route::get('/customers', [PortalCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [PortalCustomerController::class, 'show'])->name('customers.show');

        Route::get('/earnings', [PortalEarningsController::class, 'index'])->name('earnings.index');
        Route::get('/earnings/export', [PortalEarningsController::class, 'export'])->name('earnings.export');

        Route::get('/vouchers', [PortalVoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [PortalVoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [PortalVoucherController::class, 'store'])->middleware('throttle:30,1')->name('vouchers.store');
        Route::get('/vouchers/{voucher}/edit', [PortalVoucherController::class, 'edit'])->name('vouchers.edit');
        Route::put('/vouchers/{voucher}', [PortalVoucherController::class, 'update'])->name('vouchers.update');
        Route::post('/vouchers/{voucher}/toggle', [PortalVoucherController::class, 'toggle'])->name('vouchers.toggle');
        Route::delete('/vouchers/{voucher}', [PortalVoucherController::class, 'destroy'])->name('vouchers.destroy');

        Route::get('/profile', [PortalProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [PortalProfileController::class, 'update'])->name('profile.update');

        Route::get('/settings', [PortalSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/account', [PortalSettingsController::class, 'updateAccount'])->name('settings.account');
        Route::put('/settings/password', [PortalSettingsController::class, 'updatePassword'])->name('settings.password');

        Route::get('/availability', [PortalAvailabilityController::class, 'index'])->name('availability.index');
        Route::post('/availability', [PortalAvailabilityController::class, 'store'])->name('availability.store');
        Route::delete('/availability/{block}', [PortalAvailabilityController::class, 'destroy'])->name('availability.destroy');

        Route::get('/vehicles', [PortalVehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/create', [PortalVehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [PortalVehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/{vehicle}/edit', [PortalVehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [PortalVehicleController::class, 'update'])->name('vehicles.update');
        Route::get('/vehicles/{vehicle}/photos', [PortalVehiclePhotoController::class, 'index'])->name('vehicles.photos.index');
        Route::post('/vehicles/{vehicle}/photos', [PortalVehiclePhotoController::class, 'store'])->name('vehicles.photos.store');
        Route::post('/vehicles/{vehicle}/photos/{photo}/cover', [PortalVehiclePhotoController::class, 'cover'])->name('vehicles.photos.cover');
        Route::delete('/vehicles/{vehicle}/photos/{photo}', [PortalVehiclePhotoController::class, 'destroy'])->name('vehicles.photos.destroy');
    });
});


/*
|--------------------------------------------------------------------------
| Admin (platform administrators only; every route re-checks the role on the server)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/businesses', [AdminBusinessController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/{business}', [AdminBusinessController::class, 'show'])->name('businesses.show');
        Route::post('/businesses/{business}/{action}', [AdminBusinessController::class, 'act'])
            ->where('action', 'approve|reject|suspend|reinstate')
            ->middleware('throttle:60,1')
            ->name('businesses.action');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggle'])->middleware('throttle:60,1')->name('users.toggle');

        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');

        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/refunded', [AdminPaymentController::class, 'refunded'])->middleware('throttle:60,1')->name('payments.refunded');

        Route::get('/vouchers', [AdminVoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [AdminVoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [AdminVoucherController::class, 'store'])->middleware('throttle:30,1')->name('vouchers.store');
        Route::get('/vouchers/{voucher}/edit', [AdminVoucherController::class, 'edit'])->name('vouchers.edit');
        Route::put('/vouchers/{voucher}', [AdminVoucherController::class, 'update'])->name('vouchers.update');
        Route::post('/vouchers/{voucher}/toggle', [AdminVoucherController::class, 'toggle'])->name('vouchers.toggle');
        Route::delete('/vouchers/{voucher}', [AdminVoucherController::class, 'destroy'])->name('vouchers.destroy');
    });
});