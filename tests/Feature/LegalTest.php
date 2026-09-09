<?php

test('terms page renders markdown content', function () {
    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Legal/Show')
            ->where('slug', 'terminos')
            ->where('title', 'Términos de servicio')
            ->has('toc')
            ->where('html', fn (string $html) => str_contains($html, 'id="1-objeto"'))
        );
});

test('privacy page renders markdown content', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Legal/Show')
            ->where('slug', 'privacidad')
            ->where('title', 'Política de privacidad')
        );
});
