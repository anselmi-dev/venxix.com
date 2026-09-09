<?php

namespace App\Notifications;

use App\Models\Installment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstallmentDueSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Installment $installment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $purchase = $this->installment->purchase;
        $currency = $purchase->currency->symbol();
        $name = $notifiable instanceof User ? $notifiable->name : '';

        return (new MailMessage)
            ->subject(__('ui.reminders.mail_subject', ['merchant' => $purchase->merchant]))
            ->greeting(__('ui.reminders.greeting', ['name' => $name]))
            ->line(__('ui.reminders.line', [
                'number' => $this->installment->number,
                'total' => $purchase->installment_count,
                'merchant' => $purchase->merchant,
                'date' => $this->installment->due_date->format('d/m/Y'),
            ]))
            ->line(__('ui.reminders.amount', ['amount' => $currency.' '.$this->installment->amount]))
            ->action(__('ui.reminders.action'), route('cards.show', $purchase->card))
            ->line(__('ui.reminders.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $purchase = $this->installment->purchase;

        return [
            'title' => __('ui.reminders.title'),
            'description' => __('ui.reminders.description', [
                'merchant' => $purchase->merchant,
                'amount' => $purchase->currency->symbol().' '.$this->installment->amount,
                'date' => $this->installment->due_date->format('d/m'),
            ]),
            'icon' => '⏰',
            'url' => route('cards.show', $purchase->card, absolute: false),
            'installment_id' => $this->installment->id,
        ];
    }
}
