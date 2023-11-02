<?php

namespace Developer\Packages;

/**
 * A DTO that contains a move and a rating how good this move is rated
 */
class MoveResult
{
    public function __construct(
        public string $move,
        public float $score,
    ) {
    }
}
