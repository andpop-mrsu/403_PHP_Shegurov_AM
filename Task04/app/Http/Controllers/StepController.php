<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Throwable;

class StepController extends Controller
{
    public function store(Request $request, int $id): JsonResponse
    {
        try {
            $game = DB::table('games')
                ->select([
                    'id',
                    'status',
                    'current_progression_with_gap',
                    'current_progression_full',
                    'current_missing_number',
                ])
                ->where('id', $id)
                ->first();
        } catch (Throwable) {
            return response()->json([
                'error' => 'Database error while reading game',
            ], 500);
        }

        if ($game === null) {
            return response()->json([
                'error' => 'Game not found',
            ], 404);
        }

        if ((string)$game->status === 'finished') {
            return response()->json([
                'error' => 'Game is already finished',
            ], 409);
        }

        try {
            $payload = $this->decodeJsonObject($request);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 400);
        }

        $answerRaw = trim((string)($payload['answer'] ?? ''));
        $answerAsInt = filter_var($answerRaw, FILTER_VALIDATE_INT);

        if ($answerAsInt === false) {
            return response()->json([
                'error' => 'Field "answer" must be an integer',
            ], 422);
        }

        $correctAnswer = (int)$game->current_missing_number;
        $userAnswer = (int)$answerAsInt;
        $isCorrect = $userAnswer === $correctAnswer;
        $answeredAt = now()->format('Y-m-d H:i:s');

        try {
            $stepNumber = DB::transaction(function () use (
                $id,
                $answeredAt,
                $game,
                $correctAnswer,
                $answerRaw,
                $isCorrect
            ): int {
                $stepNumber = ((int)DB::table('steps')
                        ->where('game_id', $id)
                        ->max('step_number')) + 1;

                DB::table('steps')->insert([
                    'game_id' => $id,
                    'step_number' => $stepNumber,
                    'answered_at' => $answeredAt,
                    'progression_with_gap' => (string)$game->current_progression_with_gap,
                    'progression_full' => (string)$game->current_progression_full,
                    'missing_number' => $correctAnswer,
                    'user_answer' => $answerRaw,
                    'is_correct' => $isCorrect ? 1 : 0,
                ]);

                DB::table('games')
                    ->where('id', $id)
                    ->update([
                        'finished_at' => $answeredAt,
                        'status' => 'finished',
                        'result' => $isCorrect ? 'Верно' : 'Неверно',
                    ]);

                return $stepNumber;
            });
        } catch (Throwable) {
            return response()->json([
                'error' => 'Database error while saving step',
            ], 500);
        }

        return response()->json([
            'game_id' => $id,
            'step_number' => $stepNumber,
            'answered_at' => $answeredAt,
            'user_answer' => $answerRaw,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'result' => $isCorrect ? 'Верно' : 'Неверно',
            'progression_with_gap' => (string)$game->current_progression_with_gap,
            'progression_full' => (string)$game->current_progression_full,
        ]);
    }

    private function decodeJsonObject(Request $request): array
    {
        $rawBody = $request->getContent();

        if ($rawBody === '' || $rawBody === null) {
            return [];
        }

        try {
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid JSON body');
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidArgumentException('JSON body must be an object');
        }

        return $decoded;
    }
}
