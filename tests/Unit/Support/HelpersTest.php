<?php

declare(strict_types=1);

it('can create a new instance using helper function', function (): void {
    $anafInstance = anaf();

    expect($anafInstance)->toBeInstanceOf(Pristavu\Anaf\Anaf::class);
});
