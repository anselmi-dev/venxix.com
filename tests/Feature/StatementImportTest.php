<?php

use App\Enums\Currency;
use App\Models\Card;
use App\Models\Purchase;
use App\Models\StatementImport;
use App\Models\User;
use App\Services\InstallmentGeneratorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('free users cannot open the CSV importer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imports.create'))
        ->assertForbidden();
});

test('a pro user can upload and review a CSV statement', function () {
    Storage::fake('local');

    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();
    $file = UploadedFile::fake()->createWithContent(
        'estado.csv',
        "comercio,monto_total,cantidad_cuotas,moneda,primer_vencimiento\nAntel,1200.50,6,UYU,2026-10-15\n",
    );

    $response = $this->actingAs($user)
        ->post(route('imports.store'), [
            'card_id' => $card->id,
            'statement' => $file,
        ]);

    $import = StatementImport::query()->firstOrFail();

    $response->assertRedirect(route('imports.show', $import));
    expect($import->fresh()->status)->toBe('ready');
    expect($import->rows()->count())->toBe(1);
    expect($import->rows()->first())
        ->merchant->toBe('Antel')
        ->total_amount->toBe('1200.50')
        ->validation_errors->toBeNull();
    Storage::disk('local')->assertExists($import->path);
});

test('confirming selected CSV rows creates purchases and installments', function () {
    Storage::fake('local');

    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();
    $file = UploadedFile::fake()->createWithContent(
        'estado.csv',
        "comercio;monto_total;cantidad_cuotas;moneda;primer_vencimiento\nTienda;1.234,56;2;UYU;15/10/2026\n",
    );

    $this->actingAs($user)->post(route('imports.store'), [
        'card_id' => $card->id,
        'statement' => $file,
    ]);

    $import = StatementImport::query()->firstOrFail();
    $row = $import->rows()->firstOrFail();

    $this->actingAs($user)
        ->post(route('imports.confirm', $import), [
            'row_ids' => [$row->id],
        ])
        ->assertRedirect(route('panel'));

    $purchase = $user->purchases()->firstOrFail();

    expect($purchase)
        ->merchant->toBe('Tienda')
        ->total_amount->toBe('1234.56')
        ->installment_count->toBe(2);
    expect($purchase->installments()->count())->toBe(2);
    expect($import->fresh()->status)->toBe('confirmed');
    Storage::disk('local')->assertMissing($import->path);
});

test('csv rows that match an existing purchase are flagged as duplicates', function () {
    Storage::fake('local');

    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();
    $purchase = Purchase::factory()->for($user)->for($card)->create([
        'merchant' => 'Antel',
        'total_amount' => '1200.50',
        'installment_count' => 6,
        'currency' => Currency::Uyu,
        'first_due_date' => '2026-10-15',
    ]);
    (new InstallmentGeneratorService)->createForPurchase($purchase);

    $file = UploadedFile::fake()->createWithContent(
        'estado.csv',
        "comercio,monto_total,cantidad_cuotas,moneda,primer_vencimiento\nAntel,1200.50,6,UYU,2026-10-15\n",
    );

    $this->actingAs($user)->post(route('imports.store'), [
        'card_id' => $card->id,
        'statement' => $file,
    ]);

    $row = StatementImport::query()->firstOrFail()->rows()->firstOrFail();

    expect($row->validation_errors)->not->toBeNull()
        ->and($row->selected)->toBeFalse();
});

test('import create accepts a preselected card query parameter', function () {
    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('imports.create', ['card' => $card->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Imports/Create')
            ->where('preselectedCardId', $card->id));
});

test('a user cannot view another users import', function () {
    $owner = User::factory()->pro()->create();
    $other = User::factory()->pro()->create();
    $card = Card::factory()->for($owner)->create();
    $import = StatementImport::factory()->create([
        'user_id' => $owner->id,
        'card_id' => $card->id,
    ]);

    $this->actingAs($other)
        ->get(route('imports.show', $import))
        ->assertNotFound();
});

test('import routes are keyed by ulid instead of the numeric id', function () {
    $user = User::factory()->pro()->create();
    $card = Card::factory()->for($user)->create();
    $import = StatementImport::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
    ]);

    expect($import->getRouteKey())->toBe($import->ulid)
        ->and(route('imports.show', $import))->toContain($import->ulid)
        ->and(route('imports.show', $import))->not->toContain('/importaciones/'.$import->id);

    $this->actingAs($user)
        ->get('/importaciones/'.$import->id)
        ->assertNotFound();
});
