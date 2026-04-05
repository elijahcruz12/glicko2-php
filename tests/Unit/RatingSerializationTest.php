<?php

use Elijahcruz12\Glicko2\Rating;

it('can be json serialized', function () {
    $rating = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

    $json = json_encode($rating);

    expect($json)->toBeJson()
        ->and($json)->toContain('"rating"')
        ->and($json)->toContain('"rd"')
        ->and($json)->toContain('"sigma"')
        ->and($json)->toContain('"tau"');
});

it('json serialization excludes derived mu and phi', function () {
    $rating = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

    $json = json_encode($rating);

    expect($json)->not->toContain('"mu"')
        ->and($json)->not->toContain('"phi"');
});

it('can be restored from json string', function () {
    $original = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

    $restored = Rating::fromJson(json_encode($original));

    expect($restored->rating)->toBe($original->rating)
        ->and($restored->rd)->toBe($original->rd)
        ->and($restored->sigma)->toBe($original->sigma)
        ->and($restored->tau)->toBe($original->tau);
});

it('can be restored from array', function () {
    $original = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

    $restored = Rating::fromArray($original->jsonSerialize());

    expect($restored->rating)->toBe($original->rating)
        ->and($restored->rd)->toBe($original->rd)
        ->and($restored->sigma)->toBe($original->sigma)
        ->and($restored->tau)->toBe($original->tau);
});

it('correctly recomputes derived properties after deserialization', function () {
    $original = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

    $restored = Rating::fromJson(json_encode($original));

    expect($restored->mu)->toEqualWithDelta($original->mu, 0.0001)
        ->and($restored->phi)->toEqualWithDelta($original->phi, 0.0001);
});

it('can be natively serialized and unserialized', function () {
    $original = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);

    $restored = unserialize(serialize($original));

    expect($restored->rating)->toBe($original->rating)
        ->and($restored->rd)->toBe($original->rd)
        ->and($restored->sigma)->toBe($original->sigma)
        ->and($restored->tau)->toBe($original->tau)
        ->and($restored->mu)->toEqualWithDelta($original->mu, 0.0001)
        ->and($restored->phi)->toEqualWithDelta($original->phi, 0.0001);
});

it('round trips through json without precision loss', function () {
    $original = new Rating(rating: 1423.57, rd: 187.43, sigma: 0.05823, tau: 0.6);

    $restored = Rating::fromJson(json_encode($original));

    expect($restored->rating)->toEqualWithDelta($original->rating, 0.0001)
        ->and($restored->rd)->toEqualWithDelta($original->rd, 0.0001)
        ->and($restored->sigma)->toEqualWithDelta($original->sigma, 0.00001)
        ->and($restored->tau)->toEqualWithDelta($original->tau, 0.0001);
});