<?php

declare(strict_types=1);

namespace ShegurovAM\Task03\Game;

const PROGRESSION_LENGTH = 10;

function generateRound(): array
{
    $start = random_int(1, 30);
    $step = random_int(2, 10);
    $hiddenIndex = random_int(0, PROGRESSION_LENGTH - 1);

    $fullProgression = [];
    for ($i = 0; $i < PROGRESSION_LENGTH; $i++) {
        $fullProgression[] = $start + ($i * $step);
    }

    $maskedProgression = $fullProgression;
    $missingNumber = $fullProgression[$hiddenIndex];
    $maskedProgression[$hiddenIndex] = '..';

    return [
        'full' => $fullProgression,
        'masked' => $maskedProgression,
        'missing_number' => $missingNumber,
        'hidden_index' => $hiddenIndex,
    ];
}

function progressionToString(array $progression): string
{
    $parts = array_map(
        static fn (mixed $value): string => (string) $value,
        $progression
    );

    return implode(' ', $parts);
}