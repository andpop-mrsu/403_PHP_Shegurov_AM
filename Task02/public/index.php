<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/game.php';

use function ShegurovAM\Task02\Database\saveGame;
use function ShegurovAM\Task02\Game\generateRound;
use function ShegurovAM\Task02\Game\progressionToString;

$errorMessage = null;
$dbErrorMessage = isset($_SESSION['db_error']) ? (string) $_SESSION['db_error'] : null;
$playerName = isset($_SESSION['player_name']) ? (string) $_SESSION['player_name'] : '';
$currentRound = isset($_SESSION['current_round']) && is_array($_SESSION['current_round'])
    ? $_SESSION['current_round']
    : null;
$lastResult = isset($_SESSION['last_result']) && is_array($_SESSION['last_result'])
    ? $_SESSION['last_result']
    : null;

unset($_SESSION['last_result'], $_SESSION['db_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'start_game') {
        $postedName = trim((string) ($_POST['player_name'] ?? ''));

        if ($postedName === '') {
            $errorMessage = 'Введите имя игрока.';
        } else {
            $_SESSION['player_name'] = $postedName;
            $_SESSION['current_round'] = generateRound();
            redirectToHome();
        }
    }

    if ($action === 'submit_answer') {
        if ($currentRound === null || $playerName === '') {
            $errorMessage = 'Сначала начните новую игру.';
        } else {
            $answerRaw = trim((string) ($_POST['answer'] ?? ''));
            $answerAsInt = filter_var($answerRaw, FILTER_VALIDATE_INT);

            if ($answerAsInt === false) {
                $errorMessage = 'Ответ должен быть целым числом.';
            } else {
                $correctAnswer = (int) $currentRound['missing_number'];
                $isCorrect = $answerAsInt === $correctAnswer;
                $progressionWithGap = progressionToString((array) $currentRound['masked']);
                $progressionFull = progressionToString((array) $currentRound['full']);

                try {
                    saveGame([
                        'player_name' => $playerName,
                        'played_at' => date('Y-m-d H:i:s'),
                        'result' => $isCorrect ? 'Верно' : 'Неверно',
                        'progression_with_gap' => $progressionWithGap,
                        'progression_full' => $progressionFull,
                        'missing_number' => $correctAnswer,
                        'user_answer' => $answerRaw,
                    ]);
                } catch (Throwable $exception) {
                    $errorMessage = 'Не удалось загрузить историю игр из базы данных.';
                }

                $_SESSION['last_result'] = [
                    'is_correct' => $isCorrect,
                    'user_answer' => $answerRaw,
                    'correct_answer' => (string) $correctAnswer,
                    'progression_full' => $progressionFull,
                    'progression_with_gap' => $progressionWithGap,
                ];

                unset($_SESSION['current_round']);
                redirectToHome();
            }
        }
    }
}

if (isset($_SESSION['current_round']) && is_array($_SESSION['current_round'])) {
    $currentRound = $_SESSION['current_round'];
}

if (isset($_SESSION['player_name'])) {
    $playerName = (string) $_SESSION['player_name'];
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirectToHome(): never
{
    header('Location: /');
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Арифметическая прогрессия</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<main class="page">
    <h1>Игра «Арифметическая прогрессия»</h1>
    <p class="subtitle">Найдите пропущенное число в прогрессии из 10 элементов.</p>

    <nav class="nav">
        <a href="/">Игра</a>
        <a href="/history.php">История игр</a>
    </nav>

    <?php if ($errorMessage !== null): ?>
        <div class="alert alert-error"><?= escape($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($dbErrorMessage !== null): ?>
        <div class="alert alert-error"><?= escape($dbErrorMessage) ?></div>
    <?php endif; ?>

    <?php if ($playerName !== ''): ?>
        <p class="player">Игрок: <strong><?= escape($playerName) ?></strong></p>
    <?php endif; ?>

    <?php if ($lastResult !== null): ?>
        <section class="result <?= $lastResult['is_correct'] ? 'result-ok' : 'result-fail' ?>">
            <?php if ($lastResult['is_correct']): ?>
                <h2>Верно!</h2>
            <?php else: ?>
                <h2>Неверный ответ</h2>
                <p>Ваш ответ: <strong><?= escape((string) $lastResult['user_answer']) ?></strong></p>
                <p>Правильный ответ: <strong><?= escape((string) $lastResult['correct_answer']) ?></strong></p>
                <p>Полная прогрессия: <code><?= escape((string) $lastResult['progression_full']) ?></code></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($currentRound === null): ?>
        <section class="card">
            <h2>Новая игра</h2>
            <form method="post" action="/">
                <input type="hidden" name="action" value="start_game">
                <label for="player_name">Имя игрока</label>
                <input
                        type="text"
                        id="player_name"
                        name="player_name"
                        value="<?= escape($playerName) ?>"
                        required
                >
                <button type="submit">Начать игру</button>
            </form>
        </section>
    <?php else: ?>
        <section class="card">
            <h2>Текущий вопрос</h2>
            <p class="question"><code><?= escape(progressionToString((array) $currentRound['masked'])) ?></code></p>
            <form method="post" action="/">
                <input type="hidden" name="action" value="submit_answer">
                <label for="answer">Ваш ответ</label>
                <input type="text" id="answer" name="answer" autocomplete="off" required>
                <button type="submit">Проверить</button>
            </form>
        </section>
    <?php endif; ?>
</main>
</body>
</html>