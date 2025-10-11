<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Support\Coverage;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CoverageThresholdCheckerTest extends TestCase
{
    public function testPassesWhenCoverageMeetsThresholds(): void
    {
        $summary = new CoverageSummary(90.0, 80.0);
        $checker = new CoverageThresholdChecker(85.0, 75.0);

        $checker->assertSatisfies($summary);

        $this->addToAssertionCount(1);
    }

    public function testAllowsMissingBranchCoverageWhenEnabled(): void
    {
        $summary = new CoverageSummary(88.3, null);
        $checker = new CoverageThresholdChecker(80.0, 70.0, true);

        $checker->assertSatisfies($summary);

        $this->addToAssertionCount(1);
    }

    public function testFailsWhenLineCoverageBelowThreshold(): void
    {
        $summary = new CoverageSummary(70.0, 90.0);
        $checker = new CoverageThresholdChecker(80.0, 60.0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Line coverage 70.00% is below required minimum of 80.00%.');

        $checker->assertSatisfies($summary);
    }

    public function testFailsWhenBranchCoverageMissingAndNotAllowed(): void
    {
        $summary = new CoverageSummary(95.0, null);
        $checker = new CoverageThresholdChecker(80.0, 70.0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Branch coverage data is missing from the report.');

        $checker->assertSatisfies($summary);
    }

    public function testFailsWhenBranchCoverageBelowThreshold(): void
    {
        $summary = new CoverageSummary(95.0, 60.0);
        $checker = new CoverageThresholdChecker(80.0, 75.0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Branch coverage 60.00% is below required minimum of 75.00%.');

        $checker->assertSatisfies($summary);
    }

    public function testRejectsInvalidThresholds(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CoverageThresholdChecker(-1.0, 101.0);
    }
}
