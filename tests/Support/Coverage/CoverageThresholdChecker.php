<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Support\Coverage;

use InvalidArgumentException;
use RuntimeException;

final class CoverageThresholdChecker
{
    public function __construct(
        private readonly float $minimumLineCoverage,
        private readonly float $minimumBranchCoverage,
        private readonly bool $allowMissingBranchCoverage = false
    ) {
        if ($this->minimumLineCoverage < 0.0 || $this->minimumLineCoverage > 100.0) {
            throw new InvalidArgumentException('Minimum line coverage threshold must be between 0 and 100.');
        }

        if ($this->minimumBranchCoverage < 0.0 || $this->minimumBranchCoverage > 100.0) {
            throw new InvalidArgumentException('Minimum branch coverage threshold must be between 0 and 100.');
        }
    }

    public function assertSatisfies(CoverageSummary $summary): void
    {
        $lineCoverage = $summary->lineCoverage();

        if ($lineCoverage < $this->minimumLineCoverage) {
            throw new RuntimeException(sprintf(
                'Line coverage %.2f%% is below required minimum of %.2f%%.',
                $lineCoverage,
                $this->minimumLineCoverage
            ));
        }

        $branchCoverage = $summary->branchCoverage();

        if ($branchCoverage === null) {
            if ($this->allowMissingBranchCoverage) {
                return;
            }

            throw new RuntimeException('Branch coverage data is missing from the report.');
        }

        if ($branchCoverage < $this->minimumBranchCoverage) {
            throw new RuntimeException(sprintf(
                'Branch coverage %.2f%% is below required minimum of %.2f%%.',
                $branchCoverage,
                $this->minimumBranchCoverage
            ));
        }
    }
}
