<?php

namespace App\Console\Commands;

use App\Billing\PlanAccess;
use App\Enums\InstallmentStatus;
use App\Models\Installment;
use App\Notifications\InstallmentDueSoon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cuotas:send-reminders')]
#[Description('Envía recordatorios de cuotas próximas a vencer')]
class DispatchInstallmentReminders extends Command
{
    public function handle(PlanAccess $planAccess): int
    {
        $daysBefore = (int) config('cuotas.reminders.days_before', 3);
        $from = today();
        $until = today()->addDays($daysBefore);
        $sent = 0;

        Installment::query()
            ->where('status', InstallmentStatus::Pending)
            ->whereNull('reminder_sent_at')
            ->whereBetween('due_date', [$from, $until])
            ->with(['user', 'purchase'])
            ->chunkById(100, function ($installments) use ($planAccess, &$sent): void {
                foreach ($installments as $installment) {
                    if (! $planAccess->wantsReminders($installment->user)) {
                        continue;
                    }

                    $claimed = Installment::query()
                        ->whereKey($installment->id)
                        ->whereNull('reminder_sent_at')
                        ->update(['reminder_sent_at' => now()]);

                    if ($claimed !== 1) {
                        continue;
                    }

                    $installment->user->notify(
                        (new InstallmentDueSoon($installment))->afterCommit(),
                    );
                    $sent++;
                }
            });

        $this->info($sent.' recordatorios enviados.');

        return self::SUCCESS;
    }
}
