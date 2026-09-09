<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use App\Enums\InstallmentStatus;
use App\Models\Installment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstallmentExportController extends Controller
{
    public function __invoke(Request $request, PlanAccess $planAccess): StreamedResponse
    {
        abort_unless($planAccess->canExport($request->user()), 403);

        $cardId = $request->integer('card') ?: null;
        $status = $request->string('status')->toString() ?: 'pending';

        $query = Installment::query()
            ->where('user_id', $request->user()->id)
            ->with(['purchase.card'])
            ->orderBy('due_date');

        if ($status === 'pending') {
            $query->where('status', InstallmentStatus::Pending);
        } elseif ($status === 'paid') {
            $query->where('status', InstallmentStatus::Paid);
        }

        if ($cardId !== null) {
            $query->whereHas('purchase', fn ($q) => $q->where('card_id', $cardId));
        }

        $filename = 'cuotas-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'comercio',
                'tarjeta',
                'cuota',
                'total_cuotas',
                'monto',
                'moneda',
                'vencimiento',
                'estado',
            ]);

            $query->chunkById(200, function ($installments) use ($handle): void {
                foreach ($installments as $installment) {
                    $purchase = $installment->purchase;
                    $card = $purchase->card;

                    fputcsv($handle, [
                        $purchase->merchant,
                        $card->displayName().' · ····'.$card->last_four,
                        $installment->number,
                        $purchase->installment_count,
                        $installment->amount,
                        $purchase->currency->value,
                        $installment->due_date->toDateString(),
                        $installment->status->value,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
