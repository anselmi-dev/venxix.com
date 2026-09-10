<?php

use App\Billing\Gateways\DlocalGo\DlocalGoWebhookController;
use App\Billing\Gateways\MercadoPago\MercadoPagoWebhookController;
use App\Billing\Gateways\Stripe\StripeWebhookController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\InstallmentExportController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\StatementImportController;
use App\Http\Controllers\SummaryController;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use Illuminate\Support\Facades\Route;

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::post('mercadopago/webhook', MercadoPagoWebhookController::class)->name('mercadopago.webhook');
Route::post('dlocalgo/webhook', DlocalGoWebhookController::class)->name('dlocalgo.webhook');

Route::inertia('/', 'Landing')->name('home');
Route::post('locale', LocaleController::class)
    ->middleware('throttle:20,1')
    ->name('locale.update');
Route::get('precios', PricingController::class)->name('pricing');
Route::get('terminos', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('privacidad', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::middleware('guest')->prefix('auth/google')->name('auth.google.')->group(function () {
    Route::get('redirect', [GoogleAuthController::class, 'redirect'])->name('redirect');
    Route::get('callback', [GoogleAuthController::class, 'callback'])->name('callback');
});

Route::middleware(['auth', 'verified', EnsureOnboardingIsComplete::class])->group(function () {
    Route::get('dashboard', fn () => redirect()->route('panel'))->name('dashboard');

    Route::get('panel', [PanelController::class, 'index'])->name('panel');

    Route::get('tarjetas/crear', [CardController::class, 'create'])->name('cards.create');
    Route::post('tarjetas', [CardController::class, 'store'])->name('cards.store');
    Route::get('tarjetas/{card}', [CardController::class, 'show'])->name('cards.show');
    Route::get('tarjetas/{card}/editar', [CardController::class, 'edit'])->name('cards.edit');
    Route::patch('tarjetas/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('tarjetas/{card}', [CardController::class, 'destroy'])->name('cards.destroy');

    Route::get('resumen', [SummaryController::class, 'index'])->name('summary');
    Route::get('exportar/cuotas', InstallmentExportController::class)->name('installments.export');

    Route::post('compras', [PurchaseController::class, 'store'])->name('purchases.store');
    Route::patch('cuotas/{installment}', [InstallmentController::class, 'update'])->name('installments.update');

    Route::get('importaciones/plantilla', [StatementImportController::class, 'template'])->name('imports.template');
    Route::get('importaciones/crear', [StatementImportController::class, 'create'])->name('imports.create');
    Route::post('importaciones', [StatementImportController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('imports.store');
    Route::get('importaciones/{statementImport}', [StatementImportController::class, 'show'])->name('imports.show');
    Route::post('importaciones/{statementImport}/confirmar', [StatementImportController::class, 'confirm'])
        ->name('imports.confirm');

    Route::post('notas', [NoteController::class, 'store'])->name('notes.store');
    Route::patch('notas/{note}/archivar', [NoteController::class, 'archive'])->name('notes.archive');
    Route::patch('notas/{note}/restaurar', [NoteController::class, 'restore'])->name('notes.restore');
    Route::delete('notas/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

    Route::get('perfil', [ProfileController::class, 'show'])->name('perfil.show');
    Route::patch('perfil', [ProfileController::class, 'updateProfile'])->name('perfil.update');
    Route::put('perfil/contrasena', [ProfileController::class, 'updatePassword'])->name('perfil.password');
    Route::patch('perfil/moneda', [ProfileController::class, 'updateCurrency'])->name('perfil.currency');
    Route::patch('perfil/notificaciones', [ProfileController::class, 'updateNotifications'])->name('perfil.notifications');
    Route::delete('perfil', [ProfileController::class, 'destroy'])->name('perfil.destroy');

    Route::post('push/suscripcion', [PushSubscriptionController::class, 'store'])->name('push.store');
    Route::delete('push/suscripcion', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');

    Route::get('facturacion', BillingController::class)->name('billing');
    Route::post('precios/{plan}/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');
    Route::post('facturacion/cancelar', [CheckoutController::class, 'cancel'])->name('billing.cancel');
    Route::post('facturacion/reanudar', [CheckoutController::class, 'resume'])->name('billing.resume');

    Route::get('notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notificaciones/{notification}/leer', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notificaciones/leer-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::post('impersonar/salir', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
    Route::post('impersonar/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
});

Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'welcome'])->name('welcome');
    Route::post('saltear', [OnboardingController::class, 'skip'])->name('skip');
    Route::get('tarjeta', [OnboardingController::class, 'cardForm'])->name('card');
    Route::post('tarjeta', [OnboardingController::class, 'storeCard'])->name('card.store');
    Route::post('tarjeta/saltear', [OnboardingController::class, 'skipCard'])->name('card.skip');
    Route::get('moneda', [OnboardingController::class, 'currencyForm'])->name('currency');
    Route::post('moneda', [OnboardingController::class, 'storeCurrency'])->name('currency.store');
});

require __DIR__.'/settings.php';
