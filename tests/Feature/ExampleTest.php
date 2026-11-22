<?php

test('readiness endpoint reports dependency checks', function () {
    $this->getJson('/api/health/ready')
        ->assertJsonStructure([
            'status',
            'checks' => ['database', 'redis', 'mail', 'reverb'],
        ]);
});

test('readiness reports Brevo as missing when its API key is absent', function () {
    config([
        'mail.default' => 'brevo',
        'mail.from.address' => 'verified@example.com',
        'mail.mailers.brevo.api_key' => null,
    ]);

    $this->getJson('/api/health/ready')
        ->assertJsonPath('checks.mail', 'missing');
});

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertOk();
});
