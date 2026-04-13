<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Config;

    final class RedisExtractionPayload
    {
        public function __construct(
            public readonly string $taskId,
            public readonly string $projectName,
            public readonly string $redisHost,
            public readonly string $redisPassword,
            public readonly int $redisPort,
            public readonly int $redisDbIndex,
            public readonly string $redisStoreKey,
            public readonly int $redisTtlSeconds,
            public readonly string $projectRoot,
            public readonly string $outputPath,
            public readonly array $includePathPrefixes,
            public readonly array $allowedExtensions,
            public readonly array $excludePathPrefixes,
            public readonly array $excludePathPatterns,
            public readonly array $dependencyPathPrefixes,
            public readonly array $dependencySourceSubdirs,
            public readonly array $dependencyExcludePathPatterns,
            public readonly bool $writeOutputFile = true,
            public readonly bool $writeRedis = true,
            public readonly int $batchSize = 1,
            public readonly bool $debug = false,
        ) {
        }

        public static function fromBase64Json(string $payloadBase64): self
        {
            $decoded = base64_decode($payloadBase64, true);
            if ($decoded === false) {
                throw new \InvalidArgumentException('payload-base64 decode failed');
            }

            $data = json_decode($decoded, true);
            if (!is_array($data)) {
                throw new \InvalidArgumentException('payload json decode failed');
            }

            $redis = $data['redis'] ?? [];
            $extract = $data['extract'] ?? [];
            $runtime = $data['runtime'] ?? [];

            $includePathPrefixes = $extract['include_path_prefixes'] ?? ['src/'];
            if (!is_array($includePathPrefixes)) {
                $includePathPrefixes = array_values(array_filter(array_map('trim', explode(',', (string) $includePathPrefixes))));
            }

            $allowedExtensions = $extract['allowed_extensions'] ?? ['.php'];
            if (!is_array($allowedExtensions)) {
                $allowedExtensions = array_values(array_filter(array_map('trim', explode(',', (string) $allowedExtensions))));
            }

            $excludePathPrefixes = $extract['exclude_path_prefixes'] ?? [];
            if (!is_array($excludePathPrefixes)) {
                $excludePathPrefixes = array_values(array_filter(array_map('trim', explode(',', (string) $excludePathPrefixes))));
            }

            $excludePathPatterns = $extract['exclude_path_patterns'] ?? [];
            if (!is_array($excludePathPatterns)) {
                $excludePathPatterns = [];
            }

            $dependencyPathPrefixes = $extract['dependency_path_prefixes'] ?? [];
            if (!is_array($dependencyPathPrefixes)) {
                $dependencyPathPrefixes = array_values(array_filter(array_map('trim', explode(',', (string) $dependencyPathPrefixes))));
            }

            $dependencySourceSubdirs = $extract['dependency_source_subdirs'] ?? [];
            if (!is_array($dependencySourceSubdirs)) {
                $dependencySourceSubdirs = array_values(array_filter(array_map('trim', explode(',', (string) $dependencySourceSubdirs))));
            }

            $dependencyExcludePathPatterns = $extract['dependency_exclude_path_patterns'] ?? [];
            if (!is_array($dependencyExcludePathPatterns)) {
                $dependencyExcludePathPatterns = [];
            }

            return new self(
                taskId: (string) ($data['task_id'] ?? ''),
                projectName: (string) ($data['project_name'] ?? ''),
                redisHost: (string) ($redis['host'] ?? '127.0.0.1'),
                redisPassword: (string) ($redis['password'] ?? ''),
                redisPort: (int) ($redis['port'] ?? 6379),
                redisDbIndex: (int) ($redis['db_index'] ?? 0),
                redisStoreKey: (string) ($redis['store_key'] ?? 'AST_CODE'),
                redisTtlSeconds: (int) ($redis['ttl_seconds'] ?? 3600),
                projectRoot: (string) ($extract['project_root'] ?? ''),
                outputPath: (string) ($extract['output'] ?? ''),
                includePathPrefixes: $includePathPrefixes,
                allowedExtensions: $allowedExtensions,
                excludePathPrefixes: $excludePathPrefixes,
                excludePathPatterns: $excludePathPatterns,
                dependencyPathPrefixes: $dependencyPathPrefixes,
                dependencySourceSubdirs: $dependencySourceSubdirs,
                dependencyExcludePathPatterns: $dependencyExcludePathPatterns,
                writeOutputFile: (bool) ($runtime['write_output_file'] ?? true),
                writeRedis: (bool) ($runtime['write_redis'] ?? true),
                batchSize: (int) ($runtime['batch_size'] ?? 1),
                debug: (bool) ($runtime['debug'] ?? false),
            );
        }

        public function metaKey(): string
        {
            return $this->redisStoreKey . ':' . $this->taskId . ':meta';
        }

        public function filesKey(): string
        {
            return $this->redisStoreKey . ':' . $this->taskId . ':files';
        }

        public function recordsKey(): string
        {
            return $this->redisStoreKey . ':' . $this->taskId . ':records';
        }

        public function errorsKey(): string
        {
            return $this->redisStoreKey . ':' . $this->taskId . ':errors';
        }
    }