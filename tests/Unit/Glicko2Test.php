<?php

use Elijahcruz12\Glicko2\Glicko2;
use Elijahcruz12\Glicko2\MatchResult;
use Elijahcruz12\Glicko2\Outcome;
use Elijahcruz12\Glicko2\Rating;

it('correctly converts to internal scale', function () {
    $r = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

    expect($r->mu)->toEqualWithDelta(0.0, 0.0001)
        ->and($r->phi)->toEqualWithDelta(1.1513, 0.0001);
});

it('correctly computes g(φ)', function () {
    $calc = new Glicko2;

    expect($calc->g(0.1727))->toEqualWithDelta(0.9955, 0.0001)
        ->and($calc->g(0.5756))->toEqualWithDelta(0.9531, 0.0001)
        ->and($calc->g(1.7269))->toEqualWithDelta(0.7242, 0.0001);
});

it('correctly computes E(μ, μⱼ, φⱼ)', function () {
    $calc = new Glicko2;

    expect($calc->E(0, -0.5756, 0.1727))->toEqualWithDelta(0.639, 0.001)
        ->and($calc->E(0,  0.2878, 0.5756))->toEqualWithDelta(0.432, 0.001)
        ->and($calc->E(0,  1.1513, 1.7269))->toEqualWithDelta(0.303, 0.001);
});

// Shared fixtures for reflection-based tests
$paperPlayer = fn () => new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

$paperResults = fn () => [
    new MatchResult(new Rating(rating: 1400, rd: 30,  sigma: 0.06, tau: 0.5), Outcome::Win),
    new MatchResult(new Rating(rating: 1550, rd: 100, sigma: 0.06, tau: 0.5), Outcome::Loss),
    new MatchResult(new Rating(rating: 1700, rd: 300, sigma: 0.06, tau: 0.5), Outcome::Loss),
];

function invokeGlickoMethod(Glicko2 $calc, string $method, mixed ...$args): mixed
{
    try {
        return new ReflectionClass($calc)->getMethod($method)->invoke($calc, ...$args);
    } catch (ReflectionException $e) {
        throw new RuntimeException("Failed to invoke Glicko2 method '{$method}': {$e->getMessage()}", previous: $e);
    }
}

it('correctly computes v and delta', function () use ($paperPlayer, $paperResults) {
    $calc   = new Glicko2;
    $player = $paperPlayer();
    $results = $paperResults();

    $v     = invokeGlickoMethod($calc, 'computeV', $player, $results);
    $delta = invokeGlickoMethod($calc, 'computeDelta', $player, $results, $v);

    expect($v)->toEqualWithDelta(1.7785, 0.001)
        ->and($delta)->toEqualWithDelta(-0.4834, 0.001);
});

it('converges volatility to paper value', function () use ($paperPlayer, $paperResults) {
    $calc    = new Glicko2;
    $player  = $paperPlayer();
    $results = $paperResults();

    $v     = invokeGlickoMethod($calc, 'computeV', $player, $results);
    $delta = invokeGlickoMethod($calc, 'computeDelta', $player, $results, $v);
    $sigma = invokeGlickoMethod($calc, 'computeSigma', $player, $delta, $v);

    expect($sigma)->toEqualWithDelta(0.05999, 0.00001);
});

it('matches the paper final output', function () use ($paperPlayer, $paperResults) {
    $new = (new Glicko2)->calculate($paperPlayer(), $paperResults());

    expect($new->rating)->toEqualWithDelta(1464.06, 0.01)
        ->and($new->rd)->toEqualWithDelta(151.52, 0.01)
        ->and($new->sigma)->toEqualWithDelta(0.05999, 0.0001);
});

it('only increases RD when player has no games', function () use ($paperPlayer) {
    $player = $paperPlayer();
    $new    = (new Glicko2)->calculate($player, []);

    $expectedRd = sqrt(($player->phi ** 2) + ($player->sigma ** 2)) * 173.7178;

    expect($new->rating)->toBe(1500.0)
        ->and($new->sigma)->toBe(0.06)
        ->and($new->rd)->toEqualWithDelta($expectedRd, 0.0001);
});