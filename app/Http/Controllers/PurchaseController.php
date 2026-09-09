<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use App\Services\InstallmentGeneratorService;
use Illuminate\Http\RedirectResponse;

class PurchaseController extends Controller
{
    public function __construct(private InstallmentGeneratorService $generator) {}

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $purchase = Purchase::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $this->generator->createForPurchase($purchase);

        return back();
    }
}
