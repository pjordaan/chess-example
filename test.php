<?php
include 'vendor/autoload.php';

use \PChess\Chess\Chess;
use \PChess\Chess\Output\AsciiOutput;
use PChess\Chess\Piece;

$chess = new Chess();
$picker = new \Developer\Packages\BestMovePicker();
echo (new AsciiOutput())->render($chess) . PHP_EOL;
while (!$chess->gameOver()) {
    echo sprintf("Available moves: %s\n", implode(', ', $chess->moves()));
    $result = $picker->decide($chess);
    $res = $chess->move($result->move);
    echo sprintf("Next turn %1s %30s score: %.2f\n", $chess->turn, $result->move, $result->score);
    echo (new AsciiOutput())->render($chess) . PHP_EOL;
    assert($res !== null);
}
if ($chess->inCheckmate()) {
    echo ($chess->turn === Piece::WHITE ? 'black' : 'white') . ' wins!' . PHP_EOL;
} else if ($chess->inDraw()) {
    echo 'Draw' . PHP_EOL;
}


