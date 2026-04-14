<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Config;

    final class ExtractorConfig
    {
        /**
         * @param string[] $includePathPrefixes
         * @param string[] $allowedExtensions
         * @param string[] $excludePathPrefixes
         * @param string[] $excludePathPatterns
         * @param string[] $dependencyPathPrefixes
         * @param string[] $dependencySourceSubdirs
         * @param string[] $dependencyExcludePathPatterns
         */
        public function __construct(
            public readonly string $projectRoot,
            public readonly string $outputPath,
            public readonly array $includePathPrefixes = ['src/'],
            public readonly array $allowedExtensions = ['.php'],
            public readonly array $excludePathPrefixes = [],
            public readonly array $excludePathPatterns = [],
            public readonly array $dependencyPathPrefixes = [],
            public readonly array $dependencySourceSubdirs = [],
            public readonly array $dependencyExcludePathPatterns = [],
        ) {
        }
    }