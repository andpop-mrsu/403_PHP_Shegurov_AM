<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/database.php';

use function ShegurovAM\Task02\Database\fetchGames;

$games = [];
$errorMessage = null;

try {
    $games = fetchGames(200);
} catch (Throwable $exception) {
    $errorMessage = 'Не удалось загрузить историю игр из базы данных.';
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>История игр</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<main class="page">
    <h1>История игр</h1>
    <p class="subtitle">Все сыгранные раунды, сохранённые в базе данных SQLite.</p>
    <nav class="nav">
        <a href="/">Игра</a>
        <a href="/history.php">История игр</a>
    </nav>

    <?php if ($errorMessage !== null): ?>
        <div class="alert alert-error"><?= escape($errorMessage) ?></div>
    <?php elseif ($games === []): ?>
        <p>Записей пока нет. Сыграйте хотя бы один раунд на странице игры.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Игрок</th>
                    <th>Результат</th>
                    <th>Прогрессия с пропуском</th>
                    <th>Пропущенное число</th>
                    <th>Ответ игрока</th>
                    <th>Полная прогрессия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><?= escape((string) $game['id']) ?></td>
                        <td><?= escape((string) $game['played_at']) ?></td>
                        <td><?= escape((string) $game['player_name']) ?></td>
                        <td><?= escape((string) $game['result']) ?></td>
                        <td><code><?= escape((string) $game['progression_with_gap']) ?></code></td>
                        <td><?= escape((string) $game['missing_number']) ?></td>
                        <td><?= escape((string) $game['user_answer']) ?></td>
                        <td><code><?= escape((string) $game['progression_full']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>