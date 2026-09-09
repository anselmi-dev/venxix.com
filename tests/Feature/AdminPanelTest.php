<?php

use App\Models\User;
use App\Services\AdminMetricsService;
use Revoltify\Subscriptionify\Enums\SubscriptionStatus;
use Revoltify\Subscriptionify\Models\Subscription;

test('admin can access the filament panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('non admin users cannot access the filament panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('guests are redirected from the filament panel', function () {
    $this->get('/admin')
        ->assertRedirect();
});

test('admin can impersonate a user and stop impersonation', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('impersonate.start', $user))
        ->assertRedirect(route('panel'));

    expect(auth()->id())->toBe($user->id);
    expect(session('impersonate_admin_id'))->toBe($admin->id);

    $this->post(route('impersonate.stop'))
        ->assertRedirect('/admin');

    expect(auth()->id())->toBe($admin->id);
    expect(session()->has('impersonate_admin_id'))->toBeFalse();
});

test('non admin cannot impersonate users', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->post(route('impersonate.start', $target))
        ->assertForbidden();
});

test('admin cannot impersonate another admin', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('impersonate.start', $otherAdmin))
        ->assertForbidden();
});

test('admin metrics service calculates monthly recurring revenue', function () {
    $user = User::factory()->pro()->create();

    $subscription = Subscription::query()
        ->where('subscribable_id', $user->id)
        ->where('subscribable_type', User::class)
        ->first();

    expect($subscription)->not->toBeNull();

    $metrics = app(AdminMetricsService::class);

    expect($metrics->subscriptionMrr($subscription))->toBe(199.0);
    expect($metrics->monthlyRecurringRevenue())->toBeGreaterThanOrEqual(199.0);
});

test('admin metrics service counts cancellations this month', function () {
    $user = User::factory()->pro()->create();

    Subscription::query()
        ->where('subscribable_id', $user->id)
        ->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

    expect(app(AdminMetricsService::class)->cancellationsThisMonth())->toBe(1);
});
