<?php

use App\Models\Note;
use App\Models\User;

test('user can create a note within the character limit', function () {
    $user = User::factory()->pro()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), ['body' => str_repeat('a', Note::BODY_MAX_LENGTH)])
        ->assertRedirect();

    expect(Note::query()->first()->body)->toHaveLength(Note::BODY_MAX_LENGTH);
});

test('a note body over the character limit is rejected', function () {
    $user = User::factory()->pro()->create();

    $this->actingAs($user)
        ->post(route('notes.store'), ['body' => str_repeat('a', Note::BODY_MAX_LENGTH + 1)])
        ->assertInvalid(['body']);
});

test('user can archive and restore their own note', function () {
    $user = User::factory()->pro()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user)
        ->patch(route('notes.archive', $note))
        ->assertRedirect();

    expect($note->fresh()->archived_at)->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('notes.restore', $note))
        ->assertRedirect();

    expect($note->fresh()->archived_at)->toBeNull();
});

test('user cannot archive another users note', function () {
    $owner = User::factory()->pro()->create();
    $other = User::factory()->pro()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($other)
        ->patch(route('notes.archive', $note))
        ->assertForbidden();

    expect($note->fresh()->archived_at)->toBeNull();
});

test('user cannot restore another users note', function () {
    $owner = User::factory()->pro()->create();
    $other = User::factory()->pro()->create();
    $note = Note::factory()->for($owner)->archived()->create();

    $this->actingAs($other)
        ->patch(route('notes.restore', $note))
        ->assertForbidden();

    expect($note->fresh()->archived_at)->not->toBeNull();
});

test('user cannot delete another users note', function () {
    $owner = User::factory()->pro()->create();
    $other = User::factory()->pro()->create();
    $note = Note::factory()->for($owner)->create();

    $this->actingAs($other)
        ->delete(route('notes.destroy', $note))
        ->assertForbidden();

    expect(Note::query()->find($note->id))->not->toBeNull();
});
