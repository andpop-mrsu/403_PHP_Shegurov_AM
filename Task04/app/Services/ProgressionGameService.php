<?php

declare(strict_types=1);

namespace App\Services;

class ProgressionGameService
{
    public function generateRound(): array
    {
        $length = 10;
        $start = random_int(1, 30);
        $step = random_int(2, 12);
        $hiddenIndex = random_int(0, $length - 1);

        $fullProgression = [];
        for ($i = 0; $i < $length; $i++) {
            $fullProgression[] = $start + ($i * $step);
        }

        $maskedProgression = $fullProgression;
        $missingNumber = $fullProgression[$hiddenIndex];
        $maskedProgression[$hiddenIndex] = '..';

        return [
            'full' => $fullProgression,
            'masked' => $maskedProgression,
            'missing_number' => $missingNumber,
        ];
    }

    public function progressionToString(array $progression): string
    {
        $parts = array_map(static fn (mixed $value): string => (string) $value, $progression);
        return implode(' ', $parts);
    }
}
