<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated from the perfil page', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('perfil.show'))
        ->put(route('perfil.password'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('perfil.show'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
    expect(AuditLog::where('user_id', $user->id)->where('action', 'password_changed')->exists())->toBeTrue();
});
