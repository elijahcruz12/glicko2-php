<?php

namespace Elijahcruz12\Glicko2;

readonly class MatchResult
{
    public function __construct(
        public Rating  $opponent,
        public Outcome $outcome,
    ) {}
}