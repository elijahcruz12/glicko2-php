<?php

namespace Elijahcruz12\Glicko2;

readonly class Rating implements \JsonSerializable
{
    public float $mu;
    public float $phi;

    public function __construct(
        public float $rating = 1500.0,
        public float $rd = 350.0,
        public float $sigma = 0.06,
        public float $tau = 0.75,
    ) {
        $this->mu  = ($this->rating - 1500.0) / 173.7178;
        $this->phi = $this->rd / 173.7178;
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
        return new self(
            rating: $data['rating'],
            rd:     $data['rd'],
            sigma:  $data['sigma'],
            tau:    $data['tau'],
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, associative: true));
    }
}