<?php

declare(strict_types=1);

namespace Pristavu\Anaf\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pristavu\Anaf\Models\AccessToken;

class AccessTokenFactory extends Factory
{
    protected $model = AccessToken::class;

    public function definition(): array
    {
        return [
            'user_id' => 1,
            'provider' => 'anaf',
            'access_token' => $this->faker->uuid(),
            'refresh_token' => $this->faker->uuid(),
            'expires_at' => now()->addHour(),
        ];
    }
}
