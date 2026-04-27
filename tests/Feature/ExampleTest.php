<?php

use Elijahcruz12\Glicko2\Glicko2;
use Elijahcruz12\Glicko2\MatchResult;
use Elijahcruz12\Glicko2\Outcome;
use Elijahcruz12\Glicko2\Rating;

it('correctly updates ratings across multiple rating periods', function () {
    $calc = new Glicko2;

    $alice = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);
    $bob   = new Rating(rating: 1400, rd: 30,  sigma: 0.06, tau: 0.5);

    // Period 1: Alice beats Bob — use original ratings for both calculations
    $alice1 = $calc->calculate($alice, [new MatchResult($bob, Outcome::Win)]);
    $bob1   = $calc->calculate($bob,   [new MatchResult($alice, Outcome::Loss)]);

    // Period 2: Bob beats Alice — use period-1 ratings for both calculations
    $alice2 = $calc->calculate($alice1, [new MatchResult($bob1, Outcome::Loss)]);
    $bob2   = $calc->calculate($bob1,   [new MatchResult($alice1, Outcome::Win)]);

    // Alice should have lost rating after two periods (won then lost against lower-rated Bob)
    expect($alice2->rating)->toBeLessThan(1500.0)
        // Uncertainty should remain lower than the initial 200 after two active periods
        ->and($alice2->rd)->toBeLessThan(200.0)
        // Bob should have recovered some rating by beating Alice
        ->and($bob2->rating)->toBeGreaterThan($bob1->rating);
});

it('rating deviation increases when a player does not compete', function () {
    $calc   = new Glicko2;
    $player = new Rating(rating: 1500, rd: 100, sigma: 0.06, tau: 0.5);

    $updated = $calc->calculate($player, []);

    expect($updated->rd)->toBeGreaterThan($player->rd)
        ->and($updated->rating)->toBe($player->rating)
        ->and($updated->sigma)->toBe($player->sigma);
});
