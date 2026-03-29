<?php

declare(strict_types=1);

namespace ShegurovAM\Progression\Game;

function buildRound(): array
{
    $length = 10;
    $start = random_int(1, 30);
    $step = random_int(2, 12);
    $hiddenIndex = random_int(0, $length - 1);

    $progression = [];

    for ($i = 0; $i < $length; $i++) {
        $progression[] = $start + ($i * $step);
    }

    $correctAnswer = $progression[$hiddenIndex];
    $fullProgression = implode(' ', array_map('strval', $progression));

    $progression[$hiddenIndex] = '..';
    $question = implode(' ', array_map('strval', $progression));

    return [$question, $correctAnswer, $fullProgression];
}