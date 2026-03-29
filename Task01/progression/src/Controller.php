<?php

declare(strict_types=1);

namespace ShegurovAM\Progression\Controller;

use function ShegurovAM\Progression\Game\buildRound;
use function ShegurovAM\Progression\View\askAnswer;
use function ShegurovAM\Progression\View\askName;
use function ShegurovAM\Progression\View\showFailure;
use function ShegurovAM\Progression\View\showGreeting;
use function ShegurovAM\Progression\View\showQuestion;
use function ShegurovAM\Progression\View\showSuccess;
use function ShegurovAM\Progression\View\showWelcome;

function startGame(): void
{
    showWelcome();

    $name = askName();
    showGreeting($name);

    [$question, $correctAnswer, $fullProgression] = buildRound();

    showQuestion($question);
    $userAnswer = askAnswer();

    if ($userAnswer === (string) $correctAnswer) {
        showSuccess($name);
        return;
    }

    showFailure($userAnswer, (string) $correctAnswer, $fullProgression);
}
