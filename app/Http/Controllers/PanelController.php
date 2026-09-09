<?php

namespace App\Http\Controllers;

use App\Billing\PlanAccess;
use App\Models\Note;
use App\Services\ExchangeRateResolver;
use App\Services\PanelStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PanelController extends Controller
{
    public function __construct(
        private PanelStatsService $panelStats,
        private PlanAccess $planAccess,
        private ExchangeRateResolver $exchangeRateResolver,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);
        $statusFilter = $request->string('status')->toString() ?: null;
        $selectedDay = $request->has('day') ? (int) $request->integer('day') : null;

        if ($statusFilter === 'all') {
            $statusFilter = null;
        }

        $canUseNotes = $this->planAccess->hasNotes($user);
        $canUseCalendar = $this->planAccess->hasCalendar($user);

        $notes = $canUseNotes
            ? $user->notes()
                ->latest()
                ->get()
                ->map(fn ($note) => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'is_flagged' => $note->is_flagged,
                    'archived_at' => $note->archived_at?->toIso8601String(),
                    'created_at' => $note->created_at->toIso8601String(),
                ])
            : collect();

        $primaryCard = $user->cards()->oldest()->first();
        $exchangeRate = $this->exchangeRateResolver->forUser($user);

        return Inertia::render('Panel/Index', [
            'stats' => $this->panelStats->stats($user),
            'cards' => $this->panelStats->cardsForPanel($user),
            'features' => $this->planAccess->features($user),
            'showBothCurrencies' => $user->show_both_currencies,
            'preferredCurrency' => $user->preferred_currency->value,
            'exchangeRate' => $exchangeRate['rate'],
            'calendar' => [
                'year' => $year,
                'month' => $month,
                'markedDays' => $canUseCalendar ? $this->panelStats->calendarMarkedDays($user, $year, $month, $statusFilter) : [],
                'closingDays' => $canUseCalendar ? $this->panelStats->calendarClosingDays($user, $year, $month) : [],
                'events' => $canUseCalendar ? $this->panelStats->calendarEvents($user, $year, $month, $statusFilter, $selectedDay) : [],
                'selectedDay' => $canUseCalendar ? $selectedDay : null,
                'statusFilter' => $statusFilter ?? 'all',
            ],
            'notes' => $notes,
            'noteMaxLength' => Note::BODY_MAX_LENGTH,
            'summaryCardId' => $primaryCard?->id,
            'userCards' => $user->cards()->orderBy('bank')->get(['id', 'bank', 'last_four', 'alias']),
        ]);
    }
}
