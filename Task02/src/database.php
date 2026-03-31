<?php

declare(strict_types=1);

namespace ShegurovAM\Task02\Database;

use PDO;
use RuntimeException;

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databasePath = databasePath();
    $databaseDir = dirname($databasePath);

    if (!is_dir($databaseDir) && !mkdir($databaseDir, 0777, true) && !is_dir($databaseDir)) {
        throw new RuntimeException('Не удалось создать каталог для базы данных.');
    }

    $pdo = new PDO('sqlite:' . $databasePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    initializeSchema($pdo);

    return $pdo;
}

function databasePath(): string
{
    return dirname(__DIR__) . '/db/progression.sqlite';
}

function initializeSchema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            player_name TEXT NOT NULL,
            played_at TEXT NOT NULL,
            result TEXT NOT NULL,
            progression_with_gap TEXT NOT NULL,
            progression_full TEXT NOT NULL,
            missing_number INTEGER NOT NULL,
            user_answer TEXT NOT NULL
        )'
    );
}

function saveGame(array $gameData): void
{
    $pdo = getConnection();

    $statement = $pdo->prepare(
        'INSERT INTO games (
            player_name,
            played_at,
            result,
            progression_with_gap,
            progression_full,
            missing_number,
            user_answer
        ) VALUES (
            :player_name,
            :played_at,
            :result,
            :progression_with_gap,
            :progression_full,
            :missing_number,
            :user_answer
        )'
    );

    $statement->execute([
        ':player_name' => $gameData['player_name'],
        ':played_at' => $gameData['played_at'],
        ':result' => $gameData['result'],
        ':progression_with_gap' => $gameData['progression_with_gap'],
        ':progression_full' => $gameData['progression_full'],
        ':missing_number' => $gameData['missing_number'],
        ':user_answer' => $gameData['user_answer'],
    ]);
}

function fetchGames(int $limit = 100): array
{
    $safeLimit = max(1, min($limit, 500));
    $pdo = getConnection();

    $statement = $pdo->query(
        'SELECT
            id,
            player_name,
            played_at,
            result,
            progression_with_gap,
            progression_full,
            missing_number,
            user_answer
        FROM games
        ORDER BY id DESC
        LIMIT ' . $safeLimit
    );

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}
