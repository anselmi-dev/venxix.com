<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Revoltify\Subscriptionify\Enums\FeatureType;
use Revoltify\Subscriptionify\Enums\Interval;
use Revoltify\Subscriptionify\Models\Feature;
use Revoltify\Subscriptionify\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $cards = $this->feature('cards', 'Tarjetas', FeatureType::Limit);
        $calendar = $this->feature('calendar', 'Calendario', FeatureType::Toggle);
        $notes = $this->feature('notes', 'Notas', FeatureType::Toggle);
        $reminders = $this->feature('reminders', 'Recordatorios', FeatureType::Toggle);
        $export = $this->feature('export', 'Exportar', FeatureType::Toggle);
        $fx = $this->feature('fx-auto', 'Cotización automática', FeatureType::Toggle);
        $importCsv = $this->feature('import-csv', 'Importación CSV', FeatureType::Toggle);

        $paidToggles = [
            $calendar->id => $this->togglePivot(),
            $notes->id => $this->togglePivot(),
            $reminders->id => $this->togglePivot(),
            $export->id => $this->togglePivot(),
            $fx->id => $this->togglePivot(),
            $importCsv->id => $this->togglePivot(),
        ];

        $this->syncPlan('free', [
            'name' => 'Gratis',
            'description' => 'Para probar y ordenar lo básico.',
            'is_free' => true,
            'trial_days' => 0,
            'billing_period' => 1,
            'billing_interval' => Interval::Month,
            'sort_order' => 1,
        ], [
            $cards->id => $this->limitPivot(1),
        ]);

        foreach (['pro' => Interval::Month, 'pro-yearly' => Interval::Year] as $slug => $interval) {
            $this->syncPlan($slug, [
                'name' => 'Pro',
                'description' => 'Para no perderte ninguna cuota.',
                'is_free' => false,
                'trial_days' => $interval === Interval::Month ? 14 : 0,
                'billing_period' => 1,
                'billing_interval' => $interval,
                'grace_days' => 3,
                'sort_order' => 2,
            ], [
                $cards->id => $this->limitPivot(0),
                ...$paidToggles,
            ]);
        }

        foreach (['familiar' => Interval::Month, 'familiar-yearly' => Interval::Year] as $slug => $interval) {
            $this->syncPlan($slug, [
                'name' => 'Familiar',
                'description' => 'Para compartir cuentas con otra persona.',
                'is_free' => false,
                'trial_days' => 0,
                'billing_period' => 1,
                'billing_interval' => $interval,
                'grace_days' => 3,
                'sort_order' => 3,
            ], [
                $cards->id => $this->limitPivot(0),
                ...$paidToggles,
            ]);
        }
    }

    private function feature(string $slug, string $name, FeatureType $type): Feature
    {
        return Feature::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'type' => $type],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $features
     */
    private function syncPlan(string $slug, array $attributes, array $features): void
    {
        $plan = Plan::query()->updateOrCreate(
            ['slug' => $slug],
            $attributes,
        );

        $plan->features()->sync($features);
    }

    /**
     * @return array{value: string, unit_price: string, reset_period: null, reset_interval: null}
     */
    private function limitPivot(int $value): array
    {
        return [
            'value' => (string) $value,
            'unit_price' => '0',
            'reset_period' => null,
            'reset_interval' => null,
        ];
    }

    /**
     * @return array{value: string, unit_price: string, reset_period: null, reset_interval: null}
     */
    private function togglePivot(): array
    {
        return $this->limitPivot(1);
    }
}
