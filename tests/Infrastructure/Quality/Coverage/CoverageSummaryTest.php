<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Infrastructure\Quality\Coverage;

use GameItemsList\Infrastructure\Quality\Coverage\CoverageSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CoverageSummaryTest extends TestCase
{
    public function testParsesLineAndBranchCoverageWhenPresent(): void
    {
        $report = <<<'TXT'
Code Coverage Report:
 Summary:
  Lines:    87.50% (70/80)
  Branches: 78.00% (39/50)
TXT;

        $summary = CoverageSummary::fromText($report);

        self::assertSame(87.5, $summary->lineCoverage());
        self::assertSame(78.0, $summary->branchCoverage());
    }

    public function testParsesLineCoverageWhenBranchesAreMissing(): void
    {
        $report = <<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 92.42% (121/131)
TXT;

        $summary = CoverageSummary::fromText($report);

        self::assertSame(92.42, $summary->lineCoverage());
        self::assertNull($summary->branchCoverage());
    }

    public function testParsesAlternativeLabelFormats(): void
    {
        $report = <<<'TXT'
Code Coverage Report:
 Summary:
  Line Coverage: 88.00% (88/100)
  Branch Coverage: 76.50% (153/200)
TXT;

        $summary = CoverageSummary::fromText($report);

        self::assertSame(88.0, $summary->lineCoverage());
        self::assertSame(76.5, $summary->branchCoverage());
    }

    public function testRejectsReportWithoutLineCoverage(): void
    {
        $report = <<<'TXT'
Code Coverage Report:
 Summary:
  Branches: 81.00% (81/100)
TXT;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to locate line coverage in report.');

        CoverageSummary::fromText($report);
    }
}
