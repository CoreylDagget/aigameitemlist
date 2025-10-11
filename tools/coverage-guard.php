#!/usr/bin/env php
<?php

declare(strict_types=1);

use GameItemsList\Tests\Support\Coverage\CoverageSummary;
use GameItemsList\Tests\Support\Coverage\CoverageThresholdChecker;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $options = parseArguments($argv);
} catch (\InvalidArgumentException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);

    exit(1);
}

$reportPath = $options['file'] ?? null;
$minLines = $options['min-lines'] ?? null;
$minBranches = $options['min-branches'] ?? null;
$allowMissingBranches = $options['allow-missing-branches'] ?? false;

if ($reportPath === null || $minLines === null || $minBranches === null) {
    fwrite(STDERR, "Usage: coverage-guard <report-file> --min-lines=<float> --min-branches=<float> [--allow-missing-branches]\n");
    exit(1);
}

if (!is_file($reportPath)) {
    fwrite(STDERR, sprintf("Coverage report '%s' does not exist." . PHP_EOL, $reportPath));
    exit(1);
}

$reportContents = file_get_contents($reportPath);

if ($reportContents === false) {
    fwrite(STDERR, sprintf("Unable to read coverage report '%s'." . PHP_EOL, $reportPath));
    exit(1);
}

try {
    $summary = CoverageSummary::fromText($reportContents);
    $checker = new CoverageThresholdChecker($minLines, $minBranches, $allowMissingBranches);
    $checker->assertSatisfies($summary);
} catch (\InvalidArgumentException | \RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}

exit(0);

/**
 * @param string[] $argv
 * @return array<string, mixed>
 */
function parseArguments(array $argv): array
{
    $options = [];

    foreach (array_slice($argv, 1) as $argument) {
        if (!str_starts_with($argument, '--')) {
            $options['file'] = $argument;

            continue;
        }

        if ($argument === '--allow-missing-branches') {
            $options['allow-missing-branches'] = true;

            continue;
        }

        $parts = explode('=', substr($argument, 2), 2);

        if (count($parts) !== 2 || $parts[1] === '') {
            throw new \InvalidArgumentException(sprintf('Invalid argument format "%s".', $argument));
        }

        [$key, $value] = $parts;

        switch ($key) {
            case 'min-lines':
            case 'min-branches':
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException(sprintf('Option "%s" requires a numeric value.', $key));
                }

                $options[$key] = (float) $value;

                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown option "%s".', $key));
        }
    }

    return $options;
}
