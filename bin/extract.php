<?php

    declare(strict_types=1);

    require dirname(__DIR__) . '/vendor/autoload.php';

    use Coco\codeParser\Application\ExtractorApplication;
    use Coco\codeParser\Config\ExtractorConfig;
    use Coco\codeParser\Config\RedisExtractionPayload;

    $options = getopt('', [
        'project-root:',
        'output::',
        'include-path-prefixes::',
        'allowed-extensions::',
        'payload-base64::',
    ]);

    $payloadBase64 = $options['payload-base64'] ?? null;

    if ($payloadBase64) {
        $payload = RedisExtractionPayload::fromBase64Json($payloadBase64);

        $config = new ExtractorConfig(
            projectRoot: $payload->projectRoot,
            outputPath: $payload->outputPath,
            includePathPrefixes: $payload->includePathPrefixes,
            allowedExtensions: $payload->allowedExtensions,
            excludePathPrefixes: $payload->excludePathPrefixes,
            excludePathPatterns: $payload->excludePathPatterns,
            dependencyPathPrefixes: $payload->dependencyPathPrefixes,
            dependencySourceSubdirs: $payload->dependencySourceSubdirs,
            dependencyExcludePathPatterns: $payload->dependencyExcludePathPatterns,
        );

        $app = new ExtractorApplication();
        $app->run($config, $payload);

        fwrite(STDOUT, json_encode([
                'task_id' => $payload->taskId,
                'project_name' => $payload->projectName,
                'status' => 'submitted',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        exit(0);
    }

    $projectRoot = $options['project-root'] ?? null;
    $outputPath = $options['output'] ?? '/tmp/extractor.compat.result.jsonl';
    $includePathPrefixes = $options['include-path-prefixes'] ?? 'src/';
    $allowedExtensions = $options['allowed-extensions'] ?? '.php';

    if (!$projectRoot) {
        fwrite(STDERR, "用法:\n");
        fwrite(STDERR, "  php bin/extract.php --project-root=/path/to/project [--output=result.jsonl] [--include-path-prefixes=src/,vendor/] [--allowed-extensions=.php,.inc]\n");
        fwrite(STDERR, "  或者:\n");
        fwrite(STDERR, "  php bin/extract.php --payload-base64=<base64_json>\n");
        exit(1);
    }

    $includePathPrefixes = array_values(array_filter(array_map('trim', explode(',', $includePathPrefixes))));
    $allowedExtensions = array_values(array_filter(array_map('trim', explode(',', $allowedExtensions))));

    $config = new ExtractorConfig(
        projectRoot: $projectRoot,
        outputPath: $outputPath,
        includePathPrefixes: $includePathPrefixes,
        allowedExtensions: $allowedExtensions,
    );

    $app = new ExtractorApplication();
    $app->run($config);