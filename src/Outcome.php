<?php

namespace Elijahcruz12\Glicko2;

enum Outcome
{
    case Win;
    case Loss;
    case Draw;

    public function value(): float
    {
        return match($this) {
            Outcome::Win  => 1.0,
            Outcome::Loss => 0.0,
            Outcome::Draw => 0.5,
        };
    }
}