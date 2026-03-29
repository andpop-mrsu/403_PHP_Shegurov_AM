<?php

declare(strict_types=1);

namespace ShegurovAM\Task03\Database;

use PDO;
use RuntimeException;

use function ShegurovAM\Task03\Game\progressionToString;

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databasePath = databasePath();
    $databaseDir = dirname($databasePath);

    if (!is_dir($databaseDir) && !mkdir($databaseDir, 0777, true) && !is_dir($databaseDir)) {
        throw new RuntimeException('Не удалось создать каталог для базы данных');
    }

    $pdo = new PDO('sqlite:' . $databasePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

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
            started_at TEXT NOT NULL,
            finished_at TEXT,
            status TEXT NOT NULL CHECK (status IN (\'in_progress\', \'finished\')),
            result TEXT,
            current_progression_with_gap TEXT NOT NULL,
            current_progression_full TEXT NOT NULL,
            current_missing_number INTEGER NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS steps (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            step_number INTEGER NOT NULL,
            answered_at TEXT NOT NULL,
            progression_with_gap TEXT NOT NULL,
            progression_full TEXT NOT NULL,
            missing_number INTEGER NOT NULL,
            user_answer TEXT NOT NULL,
            is_correct INTEGER NOT NULL CHECK (is_correct IN (0, 1)),
            FOREIGN KEY(game_id) REFERENCES games(id) ON DELETE CASCADE,
            UNIQUE(game_id, step_number)
        )'
    );
}

function createGame(string $playerName, array $round, string $startedAt): int
{
    $pdo = getConnection();

    $statement = $pdo->prepare(
        'INSERT INTO games (
            player_name,
            started_at,
            status,
            current_progression_with_gap,
            current_progression_full,
            current_missing_number
        ) VALUES (
            :player_name,
            :started_at,
            :status,
            :current_progression_with_gap,
            :current_progression_full,
            :current_missing_number
        )'
    );

    $statement->execute([
        ':player_name' => $playerName,
        ':started_at' => $startedAt,
        ':status' => 'in_progress',
        ':current_progression_with_gap' => progressionToString((array) $round['masked']),
        ':current_progression_full' => progressionToString((array) $round['full']),
        ':current_missing_number' => (int) $round['missing_number'],
    ]);

    return (int) $pdo->lastInsertId();
}

function fetchGames(): array
{
    $pdo = getConnection();

    $statement = $pdo->query(
        'SELECT
            g.id,
            g.player_name,
            g.started_at,
            g.finished_at,
            g.status,
            g.result,
            COUNT(s.id) AS steps_count
        FROM games g
        LEFT JOIN steps s ON s.game_id = g.id
        GROUP BY
            g.id,
            g.player_name,
            g.started_at,
            g.finished_at,
            g.status,
            g.result
        ORDER BY g.id DESC'
    );

    $games = $statement->fetchAll();

    foreach ($games as &$game) {
        $game['id'] = (int) $game['id'];
        $game['steps_count'] = (int) $game['steps_count'];
    }

    return $games;
}

function fetchGameById(int $gameId): ?array
{
    $pdo = getConnection();

    $statement = $pdo->prepare(
        'SELECT
            id,
            player_name,
            started_at,
            finished_at,
            status,
            result,
            current_progression_with_gap,
            current_progression_full,
            current_missing_number
        FROM games
        WHERE id = :id'
    );

    $statement->execute([':id' => $gameId]);
    $game = $statement->fetch();

    if ($game === false) {
        return null;
    }

    $game['id'] = (int) $game['id'];
    $game['current_missing_number'] = (int) $game['current_missing_number'];

    return $game;
}

function fetchStepsByGameId(int $gameId): array
{
    $pdo = getConnection();

    $statement = $pdo->prepare(
        'SELECT
            id,
            game_id,
            step_number,
            answered_at,
            progression_with_gap,
            progression_full,
            missing_number,
            user_answer,
            is_correct
        FROM steps
        WHERE game_id = :game_id
        ORDER BY step_number ASC'
    );

    $statement->execute([':game_id' => $gameId]);
    $steps = $statement->fetchAll();

    foreach ($steps as &$step) {
        $step['id'] = (int) $step['id'];
        $step['game_id'] = (int) $step['game_id'];
        $step['step_number'] = (int) $step['step_number'];
        $step['missing_number'] = (int) $step['missing_number'];
        $step['is_correct'] = ((int) $step['is_correct']) === 1;
    }

    return $steps;
}

function nextStepNumber(int $gameId): int
{
    $pdo = getConnection();

    $statement = $pdo->prepare(
        'SELECT COALESCE(MAX(step_number), 0) + 1 AS next_step_number
        FROM steps
        WHERE game_id = :game_id'
    );

    $statement->execute([':game_id' => $gameId]);
    $row = $statement->fetch();

    return (int) $row['next_step_number'];
}

function saveStep(
    int $gameId,
    int $stepNumber,
    string $answeredAt,
    string $progressionWithGap,
    string $progressionFull,
    int $missingNumber,
    string $userAnswer,
    bool $isCorrect
): void {
    $pdo = getConnection();

    $statement = $pdo->prepare(
        'INSERT INTO steps (
            game_id,
            step_number,
            answered_at,
            progression_with_gap,
            progression_full,
            missing_number,
            user_answer,
            is_correct
        ) VALUES (
            :game_id,
            :step_number,
            :answered_at,
            :progression_with_gap,
            :progression_full,
            :missing_number,
            :user_answer,
            :is_correct
        )'
    );

    $statement->execute([
        ':game_id' => $gameId,
        ':step_number' => $stepNumber,
        ':answered_at' => $answeredAt,
        ':progression_with_gap' => $progressionWithGap,
        ':progression_full' => $progressionFull,
        ':missing_number' => $missingNumber,
        ':user_answer' => $userAnswer,
        ':is_correct' => $isCorrect ? 1 : 0,
    ]);
}

function finishGame(int $gameId, bool $isCorrect, string $finishedAt): void
{
    $pdo = getConnection();

    $statement = $pdo->prepare(
        'UPDATE games
        SET
            finished_at = :finished_at,
            status = :status,
            result = :result
        WHERE id = :id'
    );

    $statement->execute([
        ':finished_at' => $finishedAt,
        ':status' => 'finished',
        ':result' => $isCorrect ? 'Победа' : 'Поражение',
        ':id' => $gameId,
    ]);
}