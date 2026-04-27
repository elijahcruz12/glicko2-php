# glicko2

A modern PHP 8.4 implementation of [Glicko-2][1], a rating system for competitive game ladders.
Glicko-2 is a refinement of the well-known [Elo][2] rating system that adds the concepts of
rating deviation, volatility, and rating decay.

This is a clean, ground-up rewrite targeting PHP 8.4, with full type safety, immutable value
objects, and a stateless calculator. It implements the algorithm as described in the
[official Glicko-2 paper][3] by Professor Mark E. Glickman (revised March 22, 2022), including
the corrected Illinois algorithm for volatility convergence.

This library is linked to on the [official Glicko-2 page][4] as a reference implementation.

[1]: https://en.wikipedia.org/wiki/Glicko_rating_system
[2]: https://en.wikipedia.org/wiki/Elo_rating_system
[3]: https://www.glicko.net/glicko/glicko2.pdf
[4]: https://www.glicko.net/glicko.html

## Requirements

- PHP 8.4+

## Installation
```bash
composer require elijahcruz12/glicko2
```

## Usage

### Creating a player rating

For new players, use the default values. The `tau` system constant should be between
`0.3` and `1.2` depending on the application, and must be determined by estimation or
experimentation. Smaller values prevent large swings in volatility.
```php
use Elijahcruz12\Glicko2\Rating;

// New player with defaults
$player = new Rating;

// Existing player with known values
$player = new Rating(
    rating: 1500,
    rd:     200,
    sigma:  0.06,
    tau:    0.5,
);
```

### Calculating updated ratings

Ratings are calculated at the end of a **rating period** — a collection of games treated
as having occurred simultaneously. The `Glicko2` calculator is stateless and returns a
new `Rating` instance without mutating the original.
```php
use Elijahcruz12\Glicko2\Glicko2;
use Elijahcruz12\Glicko2\MatchResult;
use Elijahcruz12\Glicko2\Outcome;
use Elijahcruz12\Glicko2\Rating;

$alice   = new Rating(rating: 1500, rd: 200, sigma: 0.06, tau: 0.5);
$bob     = new Rating(rating: 1400, rd: 30,  sigma: 0.06, tau: 0.5);
$charlie = new Rating(rating: 1550, rd: 100, sigma: 0.06, tau: 0.5);
$david   = new Rating(rating: 1700, rd: 300, sigma: 0.06, tau: 0.5);

$calc = new Glicko2;

// Alice beat Bob, lost to Charlie, lost to David
$newAlice = $calc->calculate($alice, [
    new MatchResult($bob,     Outcome::Win),
    new MatchResult($charlie, Outcome::Loss),
    new MatchResult($david,   Outcome::Loss),
]);

echo $newAlice->rating; // 1464.06
echo $newAlice->rd;     // 151.52
echo $newAlice->sigma;  // 0.05999
```

### Players who did not compete

Pass an empty results array. The rating and volatility remain unchanged, but the RD
increases to reflect growing uncertainty.
```php
$newDavid = $calc->calculate($david, []);
```

### Outcomes
```php
Outcome::Win
Outcome::Loss
Outcome::Draw
```

## Serialization

`Rating` implements `JsonSerializable` and supports PHP's native `serialize()`/`unserialize()`,
making it suitable for storage in a database or use with Laravel Queues.

### JSON
```php
// Serialize
$json = json_encode($rating);
// {"rating":1500,"rd":200,"sigma":0.06,"tau":0.5}

// Restore from JSON string
$rating = Rating::fromJson($json);

// Restore from array (e.g. from a decoded JSON column)
$rating = Rating::fromArray($data);
```

The derived internal properties `mu` and `phi` are excluded from serialization — they
are always recomputed from `rating` and `rd` on construction, so round-tripping through
JSON is lossless.

### Native PHP serialization
```php
$serialized = serialize($rating);
$rating     = unserialize($serialized);
```

### Laravel

For storing a player's rating in a database column, a simple JSON cast works well:
```php
// In your migration
$table->json('rating_data')->nullable();

// In your model
use Elijahcruz12\Glicko2\Rating;

protected function rating(): Attribute
{
    return Attribute::make(
        get: fn (?string $value) => $value ? Rating::fromJson($value) : new Rating,
        set: fn (Rating $value)  => json_encode($value),
    );
}
```

For Laravel Queues, `Rating` can be passed directly as a job property — PHP's native
serialization handles it automatically.
```php
class UpdatePlayerRating implements ShouldQueue
{
    public function __construct(
        public readonly Rating $currentRating,
    ) {}
}
```

## Concepts

**Rating (`r`)** — The player's skill estimate. Defaults to `1500` for new players.

**Rating Deviation (`RD`)** — Uncertainty in the rating. High for new or inactive players,
lower for active players with consistent results. Defaults to `350`.

**Volatility (`σ`)** — How erratic the player's performance is expected to be. Defaults
to `0.06`.

**System Constant (`τ`)** — Constrains how much volatility can change per rating period.
Recommended range is `0.3`–`1.2`. Defaults to `0.75`.

## Rating periods

Glicko-2 works best when each rating period contains a moderate number of games — ideally
at least 10–15 per player. All games within a period are treated as simultaneous. The length
of a rating period is left to the administrator's discretion.

## Testing

Tests are written with [PestPHP][5] and cover the full algorithm against the numerical
example from the official Glicko-2 paper, including intermediate values for `v`, `Δ`,
and `σ′`, as well as serialization round-trips.
```bash
composer require --dev pestphp/pest
./vendor/bin/pest
```

[5]: https://pestphp.com

## License

[MIT](LICENSE)