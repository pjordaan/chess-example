<?php

namespace Developer\Packages;

use PChess\Chess\Chess;
use PChess\Chess\Piece;
/**
 * Class that returns the best move for a specific chess board and gives it a score.
 */
class BestMovePicker
{
    public const VICTORY = 1000;
    public const LOSS = -1000;
    public const DRAW = -1000;
    public const PAWN = 1;
    public const KNIGHT = 12;
    public const BISHOP = 16;
    public const ROOK = 16;
    public const QUEEN = 64;

    private array $alreadyCalculated = [];

    private function giveScore(Chess $chess): int
    {
        $score = 0;
        if ($chess->inDraw()) {
            $score += self::DRAW;
        }
        if ($chess->inCheckmate()) {
            $score += ($chess->turn === Piece::WHITE ? self::VICTORY : self::LOSS);
        }
        foreach ($chess->board as $boardCell) {
            $cellScore = match($boardCell?->getType()) {
                Piece::PAWN => self::PAWN,
                Piece::KNIGHT => self::KNIGHT,
                Piece::BISHOP => self::BISHOP,
                Piece::ROOK => self::ROOK,
                Piece::QUEEN => self::QUEEN,
                default => 0,
            };
            if ($boardCell?->getColor() === Piece::WHITE) {
                $cellScore = -$cellScore;
            }
            $score += $cellScore;
        }

        return $score;
    }

    /**
     * Returns the next best move that is beneficial for black.
     * It does this by calling this method recursively for every possible next move until someone wins.
     * It keeps track of already calculated boards to speed this process up.
     */
    public function decide(
        Chess $chess,
        int $maxRecursion = 2
    ): MoveResult {
        $hash = json_encode($chess->board);
        if (!isset($alreadyCalculated[$hash])) {
            $moves = $chess->moves();

            $bestResult = null;
            foreach ($moves as $move) {
                $san = $move->san;
                $result = $chess->move($san);
                $newHash = json_encode($chess->board);
                assert(null !== $result, 'illegal move');
                assert($newHash !== $hash);
                try {
                    $moves = $chess->moves();
                    // if chess match is over we return the current board rating.
                    if (empty($moves) || $maxRecursion <= 0) {
                        return new MoveResult($san, $this->giveScore($chess));
                    }
                    $result = $this->decide($chess, $maxRecursion - 1);
                    $result = new MoveResult($san, $result->score);

                    if (!$bestResult || ($chess->turn === Piece::WHITE && $bestResult->score < $result->score) || ($chess->turn === Piece::BLACK && $bestResult->score > $result->score)) {
                        $bestResult = $result;
                    }
                } finally {
                    $chess->undo();
                }
            }

            assert(!empty($bestResult));
            $this->alreadyCalculated[$hash] = $bestResult;
        }
        return $this->alreadyCalculated[$hash];
    }
}
