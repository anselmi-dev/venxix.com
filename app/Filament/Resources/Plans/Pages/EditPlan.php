<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Filament\Resources\Plans\PlanResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Revoltify\Subscriptionify\Models\Feature;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $cardsFeature = $this->getRecord()->features()->where('slug', 'cards')->first();
        $data['cards_limit'] = $cardsFeature?->pivot?->value ?? '1';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['cards_limit']);

        return $data;
    }

    protected function afterSave(): void
    {
        $cardsLimit = $this->form->getState()['cards_limit'] ?? null;

        if ($cardsLimit === null) {
            return;
        }

        $cardsFeature = Feature::query()->where('slug', 'cards')->first();

        if ($cardsFeature === null) {
            return;
        }

        $this->getRecord()->features()->syncWithoutDetaching([
            $cardsFeature->id => [
                'value' => (string) $cardsLimit,
                'unit_price' => '0',
                'reset_period' => null,
                'reset_interval' => null,
            ],
        ]);
    }
}
