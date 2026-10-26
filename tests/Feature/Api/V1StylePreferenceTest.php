<?php

use App\Models\User;

test('a signed-in customer can save a standard style profile', function () {
    $user = User::factory()->create();
    $plain = $user->createToken('BluLavelle app')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->patchJson('/api/v1/profile', [
            'stylePreferences' => [
                'styles' => ['french', 'glitter', 'not-a-style'],
                'shapes' => ['Almond', 'Stiletto'],
            ],
        ])
        ->assertUnprocessable();

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->patchJson('/api/v1/profile', [
            'stylePreferences' => [
                'styles' => ['french', 'glitter'],
                'shapes' => ['Almond'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.user.stylePreferences.styles', ['french', 'glitter'])
        ->assertJsonPath('data.user.stylePreferences.shapes', ['Almond']);

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.user.stylePreferences.styles.0', 'french');
});
