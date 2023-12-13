<?php

namespace Developer\Packages;

use InvalidArgumentException;
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

    private array $alreadyCalculatedRecursion = [];

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

    public function decide(
        Chess $chess,
        int $maxRecursion = 2
    ): MoveResult {
        $this->alreadyCalculated = [];
        $this->alreadyCalculatedRecursion = [];
        return $this->doDecide($chess, $maxRecursion);
    }

    /**
     * Returns the next best move that is beneficial for black. Of course if the turn is
     * white it will try to get the least beneficial move for black.
     * It does this by calling this method recursively for every possible next move until someone wins.
     * It keeps track of already calculated boards to speed this process up.
     */
    protected function doDecide(
        Chess $chess,
        int $maxRecursion
    ): MoveResult {
        $hash = json_encode($chess->board);
        if (isset($this->alreadyCalculated[$hash])) {
            $chess->move($this->alreadyCalculated[$hash]->move);
            if ($chess->inDraw()) {
                unset($this->alreadyCalculated[$hash]);
            }
            $chess->undo();
        }
        if (!isset($this->alreadyCalculated[$hash]) || (($this->alreadyCalculatedRecursion[$hash] ?? 0) < $maxRecursion)) {
            $moves = $chess->moves();
            shuffle($moves);

            $bestResult = null;
            foreach ($moves as $move) {
                $san = $move->san;
                $result = $chess->move($san);
                assert(null !== $result, 'illegal move');
                try {
                    try {
                    $moves = $chess->moves();
                    } catch (InvalidArgumentException) {
                        // sometimes the library crashes here :(
                        return new MoveResult($san, $this->giveScore($chess));
                    }
                    // if chess match is over we return the current board rating.
                    if (empty($moves) || $maxRecursion <= 0) {
                        return new MoveResult($san, $this->giveScore($chess));
                    }
                    $result = $this->doDecide($chess, $maxRecursion - 1);
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
            $this->alreadyCalculatedRecursion[$hash] = $maxRecursion;
        }
        return $this->alreadyCalculated[$hash];
    }
}
