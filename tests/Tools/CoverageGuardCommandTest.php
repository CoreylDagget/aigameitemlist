<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Tools;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/tools/coverage-guard.php';

use function runCoverageGuard;

use const COVERAGE_GUARD_USAGE;

final class CoverageGuardCommandTest extends TestCase
{
    public function testDisplaysUsageWhenHelpRequested(): void
    {
        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard(['coverage-guard.php', '--help'], $stdout, $stderr);

        self::assertSame(0, $exitCode);
        self::assertSame(COVERAGE_GUARD_USAGE, $this->getStreamContents($stdout));
        self::assertSame('', $this->getStreamContents($stderr));
    }

    public function testSucceedsWhenCoverageMeetsThresholds(): void
    {
        $reportPath = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 90.00% (90/100)
  Branches: 85.00% (85/100)
TXT);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $reportPath,
            '--min-lines=80',
            '--min-branches=75',
        ], $stdout, $stderr);

        self::assertSame(0, $exitCode);
        self::assertSame('', $this->getStreamContents($stderr));
    }

    public function testFailsWhenNoArgumentsProvided(): void
    {
        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard(['coverage-guard.php'], $stdout, $stderr);

        self::assertSame(1, $exitCode);
        self::assertSame(COVERAGE_GUARD_USAGE, $this->getStreamContents($stderr));
    }

    public function testFailsWhenReportIsMissing(): void
    {
        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            '/tmp/does-not-exist.txt',
            '--min-lines=80',
            '--min-branches=75',
        ], $stdout, $stderr);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('does not exist', $this->getStreamContents($stderr));
    }

    public function testFailsWhenCoverageBelowThreshold(): void
    {
        $reportPath = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 60.00% (60/100)
  Branches: 50.00% (50/100)
TXT);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $reportPath,
            '--min-lines=80',
            '--min-branches=75',
        ], $stdout, $stderr);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            'Line coverage 60.00% is below required minimum of 80.00%.',
            $this->getStreamContents($stderr),
        );
    }

    public function testFailsWhenRequiredThresholdsMissing(): void
    {
        $reportPath = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 88.00% (88/100)
  Branches: 81.00% (81/100)
TXT);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $reportPath,
            '--min-lines=80',
        ], $stdout, $stderr);

        self::assertSame(1, $exitCode);
        self::assertSame(COVERAGE_GUARD_USAGE, $this->getStreamContents($stderr));
    }

    public function testAllowsMissingBranchCoverageWhenFlagProvided(): void
    {
        $reportPath = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 84.00% (84/100)
TXT);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $reportPath,
            '--min-lines=80',
            '--min-branches=75',
            '--allow-missing-branches',
        ], $stdout, $stderr);

        self::assertSame(0, $exitCode);
        self::assertSame('', $this->getStreamContents($stderr));
    }

    public function testRejectsNonNumericThresholdValues(): void
    {
        $reportPath = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 90.00% (90/100)
  Branches: 80.00% (80/100)
TXT);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $reportPath,
            '--min-lines=eighty',
            '--min-branches=75',
        ], $stdout, $stderr);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            'Option "min-lines" requires a numeric value.',
            $this->getStreamContents($stderr),
        );
    }

    public function testFailsWhenUnknownOptionProvided(): void
    {
        $reportPath = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 90.00% (90/100)
  Branches: 82.00% (82/100)
TXT);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $reportPath,
            '--min-lines=80',
            '--min-branches=75',
            '--unexpected=1',
        ], $stdout, $stderr);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            'Unknown option "unexpected".',
            $this->getStreamContents($stderr),
        );
    }

    public function testAcceptsDashToReadReportFromStdIn(): void
    {
        $stdin = $this->openMemoryStream();

        fwrite($stdin, <<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 88.00% (88/100)
  Branches: 81.00% (81/100)
TXT);

        rewind($stdin);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            '-',
            '--min-lines=80',
            '--min-branches=75',
        ], $stdout, $stderr, $stdin);

        self::assertSame(0, $exitCode);
        self::assertSame('', $this->getStreamContents($stderr));
    }

    public function testFailsWhenStdInReportIsEmpty(): void
    {
        $stdin = $this->openMemoryStream();

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            '-',
            '--min-lines=80',
            '--min-branches=75',
        ], $stdout, $stderr, $stdin);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            'Coverage report from STDIN is empty.',
            $this->getStreamContents($stderr),
        );
    }

    public function testFailsWhenMultipleReportPathsProvided(): void
    {
        $firstReport = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 90.00% (90/100)
  Branches: 82.00% (82/100)
TXT);
        $secondReport = $this->createReport(<<<'TXT'
Code Coverage Report:
 Summary:
  Lines: 91.00% (91/100)
  Branches: 83.00% (83/100)
TXT);

        $stdout = $this->openMemoryStream();
        $stderr = $this->openMemoryStream();

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $firstReport,
            $secondReport,
            '--min-lines=80',
            '--min-branches=75',
        ], $stdout, $stderr);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            'Multiple coverage report paths provided. Provide only one.',
            $this->getStreamContents($stderr),
        );
    }

    /**
     * @return resource
     */
    private function openMemoryStream()
    {
        $stream = fopen('php://memory', 'w+b');

        if ($stream === false) {
            self::fail('Unable to open memory stream.');
        }

        return $stream;
    }

    /**
     * @param resource $stream
     */
    private function getStreamContents($stream): string
    {
        rewind($stream);

        return stream_get_contents($stream) ?: '';
    }

    private function createReport(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cov');

        if ($path === false) {
            self::fail('Unable to create temporary report file.');
        }

        file_put_contents($path, $contents);

        register_shutdown_function(static function () use ($path): void {
            @unlink($path);
        });

        return $path;
    }
}
