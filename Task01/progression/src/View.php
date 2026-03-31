<?php

declare(strict_types=1);

namespace ShegurovAM\Progression\View;

use function cli\line;
use function cli\prompt;

function showWelcome(): void
{
    line('Добро пожаловать в игру "Арифметическая прогрессия"!');
    line('Найдите пропущенное число в прогрессии.');
}

function askName(): string
{
    while (true) {
        $name = trim((string) prompt('Как вас зовут'));

        if ($name !== '') {
            return $name;
        }
    }
}

function showGreeting(string $name): void
{
    line('Привет, %s!', $name);
}

function showQuestion(string $question): void
{
    line('Вопрос: %s', $question);
}

function askAnswer(): string
{
    return trim((string) prompt('Ваш ответ'));
}

function showSuccess(string $name): void
{
    line('Верно!');
    line('Поздравляем, %s!', $name);
}

function showFailure(string $userAnswer, string $correctAnswer, string $progression): void
{
    line("'%s' - неверный ответ ;(. Правильный ответ: '%s'.", $userAnswer, $correctAnswer);
    line('Полная прогрессия: %s', $progression);
}