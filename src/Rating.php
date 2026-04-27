<?php

namespace Elijahcruz12\Glicko2;

readonly class Rating implements \JsonSerializable
{
    /** Glicko-2 scale factor (converts Glicko-1 units to the internal μ/φ scale). */
    public const float SCALE = 173.7178;

    public float $mu;
    public float $phi;

    public function __construct(
        public float $rating = 1500.0,
        public float $rd = 350.0,
        public float $sigma = 0.06,
        public float $tau = 0.75,
    ) {
        $this->mu  = ($this->rating - 1500.0) / self::SCALE;
        $this->phi = $this->rd / self::SCALE;
    }

    public function jsonSerialize(): array
    {
        return [
            'rating' => $this->rating,
            'rd'     => $this->rd,
            'sigma'  => $this->sigma,
            'tau'    => $this->tau,
        ];
    }

    public static function fromArray(array $data): self
    {
        $missing = array_diff(['rating', 'rd', 'sigma', 'tau'], array_keys($data));
        if ($missing !== []) {
            throw new \InvalidArgumentException('Missing required keys: ' . implode(', ', $missing));
        }

        foreach (['rating', 'rd', 'sigma', 'tau'] as $key) {
            if (!is_numeric($data[$key])) {
                throw new \InvalidArgumentException("Value for '{$key}' must be numeric, got: " . gettype($data[$key]));
            }
        }

        return new self(
            rating: (float) $data['rating'],
            rd:     (float) $data['rd'],
            sigma:  (float) $data['sigma'],
            tau:    (float) $data['tau'],
        );
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, associative: true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON does not represent an object');
        }

        return self::fromArray($data);
    }
}