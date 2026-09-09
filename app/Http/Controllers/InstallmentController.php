<?php

namespace App\Http\Controllers;

use App\Enums\InstallmentStatus;
use App\Models\Installment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{
    public function update(Request $request, Installment $installment): RedirectResponse
    {
        $this->authorize('update', $installment);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid'],
        ]);

        $status = InstallmentStatus::from($validated['status']);

        $installment->update([
            'status' => $status,
            'paid_at' => $status === InstallmentStatus::Paid ? now() : null,
        ]);

        return back();
    }
}
