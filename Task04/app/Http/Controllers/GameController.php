<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ProgressionGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use Throwable;

class GameController extends Controller
{
    public function __construct(
        private readonly ProgressionGameService $gameService
    )
    {
    }

    public function index(): JsonResponse
    {
        try {
            $games = DB::table('games as g')
                ->leftJoin('steps as s', 's.game_id', '=', 'g.id')
                ->select([
                    'g.id',
                    'g.player_name',
                    'g.started_at',
                    'g.finished_at',
                    'g.status',
                    'g.result',
                    'g.current_progression_with_gap as progression_with_gap',
                    DB::raw('COUNT(s.id) as steps_count'),
                ])
                ->groupBy([
                    'g.id',
                    'g.player_name',
                    'g.started_at',
                    'g.finished_at',
                    'g.status',
                    'g.result',
                    'g.current_progression_with_gap',
                ])
                ->orderByDesc('g.id')
                ->get()
                ->map(static function (object $game): array {
                    return [
                        'id' => (int)$game->id,
                        'player_name' => (string)$game->player_name,
                        'started_at' => (string)$game->started_at,
                        'finished_at' => $game->finished_at !== null ? (string)$game->finished_at : null,
                        'status' => (string)$game->status,
                        'result' => $game->result !== null ? (string)$game->result : null,
                        'progression_with_gap' => (string)$game->progression_with_gap,
                        'steps_count' => (int)$game->steps_count,
                    ];
                })
                ->values();
        } catch (Throwable) {
            return response()->json([
                'error' => 'Database error while fetching games',
            ], 500);
        }

        return response()->json($games);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $game = DB::table('games')
                ->select([
                    'id',
                    'player_name',
                    'started_at',
                    'finished_at',
                    'status',
                    'result',
                    'current_progression_with_gap',
                    'current_progression_full',
                    'current_missing_number',
                ])
                ->where('id', $id)
                ->first();

            if ($game === null) {
                return response()->json([
                    'error' => 'Game not found',
                ], 404);
            }

            $steps = DB::table('steps')
                ->select([
                    'id',
                    'game_id',
                    'step_number',
                    'answered_at',
                    'progression_with_gap',
                    'progression_full',
                    'missing_number',
                    'user_answer',
                    'is_correct',
                ])
                ->where('game_id', $id)
                ->orderBy('step_number')
                ->get()
                ->map(static function (object $step): array {
                    return [
                        'id' => (int)$step->id,
                        'game_id' => (int)$step->game_id,
                        'step_number' => (int)$step->step_number,
                        'answered_at' => (string)$step->answered_at,
                        'progression_with_gap' => (string)$step->progression_with_gap,
                        'progression_full' => (string)$step->progression_full,
                        'missing_number' => (int)$step->missing_number,
                        'user_answer' => (string)$step->user_answer,
                        'is_correct' => (bool)$step->is_correct,
                    ];
                })
                ->values();
        } catch (Throwable) {
            return response()->json([
                'error' => 'Database error while fetching game',
            ], 500);
        }

        return response()->json([
            'game' => [
                'id' => (int)$game->id,
                'player_name' => (string)$game->player_name,
                'started_at' => (string)$game->started_at,
                'finished_at' => $game->finished_at !== null ? (string)$game->finished_at : null,
                'status' => (string)$game->status,
                'result' => $game->result !== null ? (string)$game->result : null,
                'current_progression_with_gap' => (string)$game->current_progression_with_gap,
                'current_progression_full' => (string)$game->current_progression_full,
                'current_missing_number' => (int)$game->current_missing_number,
            ],
            'steps' => $steps,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $payload = $this->decodeJsonObject($request);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'error' => $exception->getMessage(),
            ], 400);
        }

        $playerName = trim((string)($payload['player_name'] ?? ''));
        if ($playerName === '') {
            return response()->json([
                'error' => 'Field "player_name" is required',
            ], 422);
        }

        $round = $this->gameService->generateRound();
        $startedAt = now()->format('Y-m-d H:i:s');

        try {
            $gameId = DB::table('games')->insertGetId([
                'player_name' => $playerName,
                'started_at' => $startedAt,
                'status' => 'started',
                'result' => null,
                'finished_at' => null,
                'current_progression_with_gap' => $this->gameService->progressionToString((array)$round['masked']),
                'current_progression_full' => $this->gameService->progressionToString((array)$round['full']),
                'current_missing_number' => (int)$round['missing_number'],
            ]);
        } catch (Throwable) {
            return response()->json([
                'error' => 'Database error while creating game',
            ], 500);
        }

        return response()->json([
            'id' => (int)$gameId,
            'player_name' => $playerName,
            'started_at' => $startedAt,
            'status' => 'started',
            'progression' => $round['masked'],
        ], 201);
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
