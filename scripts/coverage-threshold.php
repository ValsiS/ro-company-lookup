<?php

declare(strict_types=1);

$path = $argv[1] ?? 'build/logs/clover.xml';
$thresholdInput = $argv[2] ?? getenv('COVERAGE_THRESHOLD') ?? '50';
$threshold = (float) $thresholdInput;

if (! is_file($path)) {
    fwrite(STDERR, sprintf("Coverage file not found: %s\n", $path));
    exit(1);
}

$xml = simplexml_load_file($path);
if ($xml === false) {
    fwrite(STDERR, "Unable to parse coverage XML.\n");
    exit(1);
}

$metrics = $xml->project->metrics ?? null;
if ($metrics === null) {
    $metricsNodes = $xml->xpath('//metrics');
    $metrics = $metricsNodes[0] ?? null;
}

if (! $metrics) {
    fwrite(STDERR, "Coverage metrics not found.\n");
    exit(1);
}

$statements = (int) ($metrics['statements'] ?? 0);
$covered = (int) ($metrics['coveredstatements'] ?? 0);

$coverage = $statements > 0 ? ($covered / $statements) * 100 : 100.0;

printf("Line coverage: %.2f%% (threshold: %.2f%%)\n", $coverage, $threshold);

if ($coverage + 0.00001 < $threshold) {
    fwrite(STDERR, "Coverage threshold not met.\n");
    exit(1);
}
