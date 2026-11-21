<?php

test('readiness endpoint reports dependency checks', function () {
    $this->getJson('/api/health/ready')
        ->assertJsonStructure([
            'status',
            'checks' => ['database', 'redis', 'mail', 'reverb'],
        ]);
});

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk();
});
