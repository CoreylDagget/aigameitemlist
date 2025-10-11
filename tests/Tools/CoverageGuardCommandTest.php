<?php

declare(strict_types=1);

namespace GameItemsList\Tests\Tools;

use PHPUnit\Framework\TestCase;

use function runCoverageGuard;

use const COVERAGE_GUARD_USAGE;

final class CoverageGuardCommandTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/../tools/coverage-guard.php';
    }

    public function testDisplaysUsageWhenHelpRequested(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');

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

        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');

        $exitCode = runCoverageGuard([
            'coverage-guard.php',
            $reportPath,
            '--min-lines=80',
            '--min-branches=75',
        ], $stdout, $stderr);

        self::assertSame(0, $exitCode);
        self::assertSame('', $this->getStreamContents($stderr));
    }

    public function testFailsWhenReportIsMissing(): void
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');

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

        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');

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
