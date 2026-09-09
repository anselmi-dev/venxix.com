<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __invoke(Request $request, PlanAccess $planAccess): Response
    {
        $user = $request->user();
        $subscription = $user->subscription();
        $plan = $subscription?->getPlan();
        $family = $planAccess->family($user);
        $intervalIsYear = $plan?->getBillingInterval()->value === 'year';
        $interval = $intervalIsYear ? __('ui.billing.year') : __('ui.billing.month');
        $price = (string) config('cuotas.prices.'.$family.'.'.($intervalIsYear ? 'yearly' : 'monthly'));
        $createdAt = $user->created_at;

        $invoices = $user->invoices()
            ->latest('paid_at')
            ->get()
            ->map(fn ($invoice) => [
                'id' => (string) $invoice->id,
                'date' => ($invoice->paid_at ?? $invoice->created_at)->translatedFormat('d M Y'),
                'description' => $invoice->description,
                'amount' => $invoice->amount,
                'status' => $invoice->status === 'paid' ? __('ui.billing.paid') : $invoice->status,
                'url' => $invoice->hosted_invoice_url,
            ]);

        $paidThisYear = $user->invoices()
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->get();

        return Inertia::render('Billing/Index', [
            'plan' => [
                'name' => $plan?->getName() ?? __('ui.common.free'),
                'slug' => $family,
                'price' => $price,
                'interval' => $interval,
                'status_label' => $subscription?->onTrial() ? __('ui.billing.trial') : ($subscription?->onGracePeriod() ? __('ui.billing.cancelled') : __('ui.billing.current')),
                'renews_at' => $plan?->isFree() ? null : $subscription?->getEndsAt()?->translatedFormat('d M Y'),
                'on_grace_period' => $subscription?->onGracePeriod() ?? false,
                'is_free' => $plan?->isFree() ?? true,
                'provider' => match ($user->billing_provider) {
                    'mercadopago' => 'Mercado Pago',
                    'stripe' => 'Stripe',
                    default => null,
                },
            ],
            'stats' => [
                'next_charge' => $plan?->isFree() ? null : '$ '.$price,
                'paid_this_year' => number_format((float) $paidThisYear->sum('amount'), 2, '.', ''),
                'payment_count' => $paidThisYear->count(),
                'member_since' => $createdAt->translatedFormat('M Y'),
            ],
            'paymentMethod' => $user->pm_last_four ? [
                'brand' => strtoupper((string) $user->pm_brand),
                'last_four' => $user->pm_last_four,
                'expires' => $user->pm_exp_month && $user->pm_exp_year
                    ? str_pad((string) $user->pm_exp_month, 2, '0', STR_PAD_LEFT).'/'.$user->pm_exp_year
                    : '—',
            ] : null,
            'invoices' => $invoices,
            'checkoutSuccess' => $request->string('checkout')->toString() === 'success',
        ]);
    }
}
