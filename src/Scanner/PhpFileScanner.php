<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Scanner;

    use FilesystemIterator;
    use RecursiveDirectoryIterator;
    use RecursiveIteratorIterator;
    use SplFileInfo;

    final class PhpFileScanner
    {
        /**
         * @param string   $projectRoot
         * @param string[] $includePathPrefixes
         * @param string[] $allowedExtensions
         * @param string[] $excludePathPrefixes
         * @param string[] $excludePathPatterns
         * @param string[] $dependencyPathPrefixes
         * @param string[] $dependencySourceSubdirs
         * @param string[] $dependencyExcludePathPatterns
         *
         * @return string[]
         */
        public function scan(
            string $projectRoot,
            array $includePathPrefixes = ['src/'],
            array $allowedExtensions = ['.php'],
            array $excludePathPrefixes = [],
            array $excludePathPatterns = [],
            array $dependencyPathPrefixes = [],
            array $dependencySourceSubdirs = [],
            array $dependencyExcludePathPatterns = [],
        ): array {
            $projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');
            $files = [];

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }

                if (!$file->isFile()) {
                    continue;
                }

                $fullPath = str_replace('\\', '/', $file->getPathname());
                $relativePath = ltrim(substr($fullPath, strlen($projectRoot)), '/');

                if (!$this->matchesPathPrefixes($relativePath, $includePathPrefixes)) {
                    continue;
                }

                if ($this->matchesPathPrefixes($relativePath, $excludePathPrefixes)) {
                    continue;
                }

                if ($this->matchesPatterns($relativePath, $excludePathPatterns)) {
                    continue;
                }

                if (!$this->matchesExtensions($relativePath, $allowedExtensions)) {
                    continue;
                }
/*
                $dependencyPrefix = $this->matchDependencyPrefix($relativePath, $dependencyPathPrefixes);
                if ($dependencyPrefix !== null) {
                    if (!$this->matchesDependencySourceSubdirs($relativePath, $dependencyPrefix, $dependencySourceSubdirs)) {
                        continue;
                    }

                    if ($this->matchesPatterns($relativePath, $dependencyExcludePathPatterns)) {
                        continue;
                    }
                }
*/

                $dependencyPrefix = $this->matchDependencyPrefix($relativePath, $dependencyPathPrefixes);
                if ($dependencyPrefix !== null) {
                    if ($this->matchesPatterns($relativePath, $dependencyExcludePathPatterns)) {
                        // 这里不直接排除 tests/examples/docs 等辅助代码，后续由 role 分类控制索引与召回优先级
                        // 仅保留这个钩子给真正需要的极端排除规则
                    }
                }

                $files[] = $fullPath;
            }

            sort($files);

            return $files;
        }

        /**
         * @param string   $relativePath
         * @param string[] $prefixes
         */
        private function matchesPathPrefixes(string $relativePath, array $prefixes): bool
        {
            foreach ($prefixes as $prefix) {
                $prefix = trim(str_replace('\\', '/', $prefix));
                if ($prefix === '') {
                    continue;
                }

                if (str_starts_with($relativePath, $prefix)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * @param string   $relativePath
         * @param string[] $extensions
         */
        private function matchesExtensions(string $relativePath, array $extensions): bool
        {
            foreach ($extensions as $ext) {
                $ext = trim($ext);
                if ($ext === '') {
                    continue;
                }

                if (str_ends_with($relativePath, $ext)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * @param string   $relativePath
         * @param string[] $patterns
         */
        private function matchesPatterns(string $relativePath, array $patterns): bool
        {
            foreach ($patterns as $pattern) {
                if (!is_string($pattern) || trim($pattern) === '') {
                    continue;
                }

                if (@preg_match('#' . $pattern . '#i', $relativePath) === 1) {
                    return true;
                }
            }

            return false;
        }

        /**
         * @param string   $relativePath
         * @param string[] $dependencyPathPrefixes
         */
        private function matchDependencyPrefix(string $relativePath, array $dependencyPathPrefixes): ?string
        {
            foreach ($dependencyPathPrefixes as $prefix) {
                $prefix = trim(str_replace('\\', '/', $prefix));
                if ($prefix === '') {
                    continue;
                }

                if (str_starts_with($relativePath, $prefix)) {
                    return $prefix;
                }
            }

            return null;
        }

        /**
         * @param string   $relativePath
         * @param string   $dependencyPrefix
         * @param string[] $dependencySourceSubdirs
         */
        private function matchesDependencySourceSubdirs(
            string $relativePath,
            string $dependencyPrefix,
            array $dependencySourceSubdirs
        ): bool {
            if ($dependencySourceSubdirs === []) {
                return true;
            }

            $remaining = ltrim(substr($relativePath, strlen($dependencyPrefix)), '/');
            if ($remaining === '') {
                return false;
            }

            $parts = array_values(array_filter(explode('/', $remaining), static fn($item): bool => $item !== ''));
            if (count($parts) < 2) {
                return false;
            }

            $subPath = $parts[1] . '/';

            foreach ($dependencySourceSubdirs as $subdir) {
                $subdir = trim(str_replace('\\', '/', $subdir));
                if ($subdir === '') {
                    continue;
                }

                if ($subPath === $subdir) {
                    return true;
                }
            }

            return false;
        }
    }