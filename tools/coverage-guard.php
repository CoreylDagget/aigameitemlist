#!/usr/bin/env php
<?php

declare(strict_types=1);

use GameItemsList\Infrastructure\Quality\Coverage\CoverageSummary;
use GameItemsList\Infrastructure\Quality\Coverage\CoverageThresholdChecker;

require_once __DIR__ . '/../vendor/autoload.php';

const COVERAGE_GUARD_USAGE = "Usage: coverage-guard <report-file> --min-lines=<float> --min-branches=<float> [--allow-missing-branches]\n";

/**
 * @param string[] $argv
 * @param resource|null $stdout
 * @param resource|null $stderr
 * @param resource|null $stdin
 */
function runCoverageGuard(array $argv, $stdout = null, $stderr = null, $stdin = null): int
{
    $stdout ??= STDOUT;
    $stderr ??= STDERR;
    $stdin ??= STDIN;

    try {
        $options = parseArguments($argv);
    } catch (\InvalidArgumentException $exception) {
        fwrite($stderr, $exception->getMessage() . PHP_EOL);

        return 1;
    }

    if (($options['help'] ?? false) === true) {
        fwrite($stdout, COVERAGE_GUARD_USAGE);

        return 0;
    }

    $reportPath = $options['file'] ?? null;
    $minLines = $options['min-lines'] ?? null;
    $minBranches = $options['min-branches'] ?? null;
    $allowMissingBranches = $options['allow-missing-branches'] ?? false;

    if ($reportPath === null || $minLines === null || $minBranches === null) {
        fwrite($stderr, COVERAGE_GUARD_USAGE);

        return 1;
    }

    if ($reportPath === '-') {
        $reportContents = stream_get_contents($stdin);

        if ($reportContents === false) {
            fwrite($stderr, 'Unable to read coverage report from STDIN.' . PHP_EOL);

            return 1;
        }

        if ($reportContents === '') {
            fwrite($stderr, 'Coverage report from STDIN is empty.' . PHP_EOL);

            return 1;
        }
    } else {
        if (!is_file($reportPath)) {
            fwrite($stderr, sprintf("Coverage report '%s' does not exist." . PHP_EOL, $reportPath));

            return 1;
        }

        $reportContents = file_get_contents($reportPath);

        if ($reportContents === false) {
            fwrite($stderr, sprintf("Unable to read coverage report '%s'." . PHP_EOL, $reportPath));

            return 1;
        }
    }

    try {
        $summary = CoverageSummary::fromText($reportContents);
        $checker = new CoverageThresholdChecker($minLines, $minBranches, $allowMissingBranches);
        $checker->assertSatisfies($summary);
    } catch (\InvalidArgumentException | \RuntimeException $exception) {
        fwrite($stderr, $exception->getMessage() . PHP_EOL);

        return 1;
    }

    return 0;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(runCoverageGuard($argv));
}

/**
 * @param string[] $argv
 * @return array<string, mixed>
 */
function parseArguments(array $argv): array
{
    $options = [];

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--help' || $argument === '-h') {
            $options['help'] = true;

            continue;
        }

        if (!str_starts_with($argument, '--')) {
            if (array_key_exists('file', $options)) {
                throw new \InvalidArgumentException('Multiple coverage report paths provided. Provide only one.');
            }

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
