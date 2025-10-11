<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Support\Coverage;

use InvalidArgumentException;

final class CoverageSummary
{
    public function __construct(
        private readonly float $lineCoverage,
        private readonly ?float $branchCoverage
    ) {
        if ($this->lineCoverage < 0.0 || $this->lineCoverage > 100.0) {
            throw new InvalidArgumentException('Line coverage must be between 0 and 100.');
        }

        if ($this->branchCoverage !== null && ($this->branchCoverage < 0.0 || $this->branchCoverage > 100.0)) {
            throw new InvalidArgumentException('Branch coverage must be between 0 and 100.');
        }
    }

    public static function fromText(string $report): self
    {
        $lineCoverage = self::extractPercentage($report, 'lines');

        if ($lineCoverage === null) {
            throw new InvalidArgumentException('Unable to locate line coverage in report.');
        }

        $branchCoverage = self::extractPercentage($report, 'branches');

        return new self($lineCoverage, $branchCoverage);
    }

    public function lineCoverage(): float
    {
        return $this->lineCoverage;
    }

    public function branchCoverage(): ?float
    {
        return $this->branchCoverage;
    }

    private static function extractPercentage(string $report, string $metric): ?float
    {
        $labels = match ($metric) {
            'lines' => ['Lines', 'Line Coverage'],
            'branches' => ['Branches', 'Branch Coverage'],
            default => [ucfirst($metric)],
        };

        foreach ($labels as $label) {
            $pattern = sprintf('/^\s*%s:?\s+(\d+(?:\.\d+)?)%%/mi', preg_quote($label, '/'));

            if (preg_match($pattern, $report, $matches) === 1) {
                return (float) $matches[1];
            }
        }

        return null;
    }
}
