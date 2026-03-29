<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/game.php';
require_once __DIR__ . '/../src/database.php';

use ShegurovAM\Task03\Game;
use ShegurovAM\Task03\Database;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$frontendHandler = static function (
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    $file = __DIR__ . '/index.html';

    if (!is_file($file)) {
        return jsonResponse($response, ['error' => 'Файл index.html не найден'], 500);
    }

    $content = file_get_contents($file);
    if ($content === false) {
        return jsonResponse($response, ['error' => 'Не удалось прочитать index.html'], 500);
    }

    $response->getBody()->write($content);

    return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
};

$listGamesHandler = static function (
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    try {
        $games = Database\fetchGames();
    } catch (Throwable $exception) {
        return jsonResponse($response, ['error' => 'Ошибка при чтении списка игр из базы данных'], 500);
    }

    return jsonResponse($response, $games);
};

$getGameHandler = static function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {
    $gameId = (int) ($args['id'] ?? 0);

    if ($gameId <= 0) {
        return jsonResponse($response, ['error' => 'Некорректный идентификатор игры'], 400);
    }

    try {
        $game = Database\fetchGameById($gameId);

        if ($game === null) {
            return jsonResponse($response, ['error' => 'Игра не найдена'], 404);
        }

        $steps = Database\fetchStepsByGameId($gameId);
    } catch (Throwable $exception) {
        return jsonResponse($response, ['error' => 'Ошибка при чтении игры из базы данных'], 500);
    }

    return jsonResponse($response, [
        'game' => [
            'id' => $game['id'],
            'player_name' => $game['player_name'],
            'started_at' => $game['started_at'],
            'finished_at' => $game['finished_at'],
            'status' => $game['status'],
            'result' => $game['result'],
            'progression_with_gap' => $game['current_progression_with_gap'],
            'progression_full' => $game['current_progression_full'],
            'missing_number' => $game['current_missing_number'],
        ],
        'steps' => $steps,
    ]);
};

$createGameHandler = static function (
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    try {
        $payload = parseJsonObject($request);
    } catch (InvalidArgumentException $exception) {
        return jsonResponse($response, ['error' => $exception->getMessage()], 400);
    }

    $playerName = trim((string) ($payload['player_name'] ?? ''));

    if ($playerName === '') {
        return jsonResponse($response, ['error' => 'Поле "player_name" обязательно'], 422);
    }

    if (mb_strlen($playerName) > 50) {
        return jsonResponse($response, ['error' => 'Имя игрока должно быть не длиннее 50 символов'], 422);
    }

    $round = Game\generateRound();
    $startedAt = date('Y-m-d H:i:s');

    try {
        $gameId = Database\createGame($playerName, $round, $startedAt);
    } catch (Throwable $exception) {
        return jsonResponse($response, ['error' => 'Ошибка при создании новой игры'], 500);
    }

    return jsonResponse($response, [
        'id' => $gameId,
        'player_name' => $playerName,
        'started_at' => $startedAt,
        'progression' => $round['masked'],
        'message' => 'Новая игра создана',
    ], 201);
};

$createStepHandler = static function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $args
): ResponseInterface {
    $gameId = (int) ($args['id'] ?? 0);

    if ($gameId <= 0) {
        return jsonResponse($response, ['error' => 'Некорректный идентификатор игры'], 400);
    }

    try {
        $game = Database\fetchGameById($gameId);
    } catch (Throwable $exception) {
        return jsonResponse($response, ['error' => 'Ошибка при чтении игры из базы данных'], 500);
    }

    if ($game === null) {
        return jsonResponse($response, ['error' => 'Игра не найдена'], 404);
    }

    if ((string) $game['status'] === 'finished') {
        return jsonResponse($response, ['error' => 'Игра уже завершена'], 409);
    }

    try {
        $payload = parseJsonObject($request);
    } catch (InvalidArgumentException $exception) {
        return jsonResponse($response, ['error' => $exception->getMessage()], 400);
    }

    $answerRaw = trim((string) ($payload['answer'] ?? ''));
    if ($answerRaw === '') {
        return jsonResponse($response, ['error' => 'Поле "answer" обязательно'], 422);
    }

    $answerAsInt = filter_var($answerRaw, FILTER_VALIDATE_INT);
    if ($answerAsInt === false) {
        return jsonResponse($response, ['error' => 'Поле "answer" должно быть целым числом'], 422);
    }

    $correctAnswer = (int) $game['current_missing_number'];
    $isCorrect = ((int) $answerAsInt) === $correctAnswer;
    $answeredAt = date('Y-m-d H:i:s');

    try {
        $stepNumber = Database\nextStepNumber($gameId);

        Database\saveStep(
            $gameId,
            $stepNumber,
            $answeredAt,
            (string) $game['current_progression_with_gap'],
            (string) $game['current_progression_full'],
            $correctAnswer,
            $answerRaw,
            $isCorrect
        );

        Database\finishGame($gameId, $isCorrect, $answeredAt);
    } catch (Throwable $exception) {
        return jsonResponse($response, ['error' => 'Ошибка при сохранении хода'], 500);
    }

    return jsonResponse($response, [
        'game_id' => $gameId,
        'step_number' => $stepNumber,
        'answered_at' => $answeredAt,
        'user_answer' => $answerRaw,
        'correct_answer' => $correctAnswer,
        'is_correct' => $isCorrect,
        'result' => $isCorrect ? 'Победа' : 'Поражение',
        'progression_with_gap' => (string) $game['current_progression_with_gap'],
        'progression_full' => (string) $game['current_progression_full'],
        'message' => $isCorrect
            ? 'Верно! Вы угадали пропущенное число.'
            : 'Неверно. Игра завершена.',
    ]);
};

$app->get('/', $frontendHandler);
$app->get('/index.php', $frontendHandler);
$app->get('/index.php/', $frontendHandler);

$app->get('/games', $listGamesHandler);
$app->get('/index.php/games', $listGamesHandler);

$app->get('/games/{id:[0-9]+}', $getGameHandler);
$app->get('/index.php/games/{id:[0-9]+}', $getGameHandler);

$app->post('/games', $createGameHandler);
$app->post('/index.php/games', $createGameHandler);

$app->post('/step/{id:[0-9]+}', $createStepHandler);
$app->post('/index.php/step/{id:[0-9]+}', $createStepHandler);

$app->run();

function parseJsonObject(ServerRequestInterface $request): array
{
    $rawBody = trim((string) $request->getBody());

    if ($rawBody === '') {
        return [];
    }

    try {
        $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new InvalidArgumentException('Тело запроса должно содержать корректный JSON');
    }

    if (!is_array($decoded)) {
        throw new InvalidArgumentException('JSON должен быть объектом');
    }

    return $decoded;
}

function jsonResponse(ResponseInterface $response, mixed $payload, int $status = 200): ResponseInterface
{
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        $response->getBody()->write('{"error":"Не удалось сформировать JSON"}');

        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    $response->getBody()->write($encoded);

    return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json; charset=utf-8');
}