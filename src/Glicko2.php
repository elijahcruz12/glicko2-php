<?php

namespace Elijahcruz12\Glicko2;

class Glicko2
{
    private const float SCALE = 173.7178;
    private const float EPSILON = 0.000001;

    /**
     * @param MatchResult[] $results
     */
    public function calculate(Rating $player, array $results): Rating
    {
        // No games played — only Step 6 applies
        if (count($results) === 0) {
            $phiStar = sqrt(($player->phi ** 2) + ($player->sigma ** 2));
            return $this->toRating($player->mu, $phiStar, $player->sigma, $player->tau);
        }

        // Step 3 — estimated variance
        $v = $this->computeV($player, $results);

        // Step 4 — estimated improvement
        $delta = $this->computeDelta($player, $results, $v);

        // Step 5 — new volatility via Illinois algorithm
        $sigmaPrime = $this->computeSigma($player, $delta, $v);

        // Step 6 — pre-rating period RD
        $phiStar = sqrt(($player->phi ** 2) + ($sigmaPrime ** 2));

        // Step 7 — new phi and mu
        $phiPrime = 1.0 / sqrt((1.0 / ($phiStar ** 2)) + (1.0 / $v));
        $muPrime  = $player->mu + ($phiPrime ** 2) * $this->computeDeltaSum($player, $results);

        // Step 8 — convert back and return
        return $this->toRating($muPrime, $phiPrime, $sigmaPrime, $player->tau);
    }

    public function g(float $phi): float
    {
        return 1.0 / sqrt(1.0 + (3.0 * $phi ** 2) / (M_PI ** 2));
    }

    public function E(float $mu, float $muJ, float $phiJ): float
    {
        return 1.0 / (1.0 + exp(-$this->g($phiJ) * ($mu - $muJ)));
    }

    private function computeV(Rating $player, array $results): float
    {
        $sum = 0.0;

        foreach ($results as $result) {
            $g = $this->g($result->opponent->phi);
            $e = $this->E($player->mu, $result->opponent->mu, $result->opponent->phi);
            $sum += ($g ** 2) * $e * (1.0 - $e);
        }

        return 1.0 / $sum;
    }

    private function computeDeltaSum(Rating $player, array $results): float
    {
        $sum = 0.0;

        foreach ($results as $result) {
            $g = $this->g($result->opponent->phi);
            $e = $this->E($player->mu, $result->opponent->mu, $result->opponent->phi);
            $sum += $g * ($result->outcome->value() - $e);
        }

        return $sum;
    }

    private function computeDelta(Rating $player, array $results, float $v): float
    {
        return $v * $this->computeDeltaSum($player, $results);
    }

    private function computeSigma(Rating $player, float $delta, float $v): float
    {
        $a      = log($player->sigma ** 2);
        $phi    = $player->phi;
        $tau    = $player->tau;
        $deltaSq = $delta ** 2;
        $phiSq  = $phi ** 2;

        $f = function (float $x) use ($a, $deltaSq, $phiSq, $v, $tau): float {
            $ex  = exp($x);
            $tmp = $phiSq + $v + $ex;
            return (($ex * ($deltaSq - $tmp)) / (2.0 * $tmp ** 2)) - (($x - $a) / ($tau ** 2));
        };

        // Set initial A
        $A  = $a;
        $fA = $f($A);

        // Set initial B — two branches per the paper
        if ($deltaSq > $phiSq + $v) {
            $B = log($deltaSq - $phiSq - $v);
        } else {
            $k = 1;
            while ($f($a - $k * $tau) < 0) {
                $k++;
            }
            $B = $a - $k * $tau;
        }

        $fB = $f($B);

        // Illinois algorithm
        while (abs($B - $A) > self::EPSILON) {
            $C  = $A + ($A - $B) * $fA / ($fB - $fA);
            $fC = $f($C);

            // 2022 paper correction: ≤ 0, not < 0
            if ($fC * $fB <= 0) {
                $A  = $B;
                $fA = $fB;
            } else {
                $fA /= 2.0;
            }

            $B  = $C;
            $fB = $fC;
        }

        return exp($A / 2.0);
    }

    private function toRating(float $mu, float $phi, float $sigma, float $tau): Rating
    {
        return new Rating(
            rating: self::SCALE * $mu + 1500.0,
            rd:     self::SCALE * $phi,
            sigma:  $sigma,
            tau:    $tau,
        );
    }
}