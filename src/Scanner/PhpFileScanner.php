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
         * @param string[] $vendorIncludePathPrefixes
         * @param string[] $vendorExcludePathPrefixes
         * @param string[] $vendorExcludePathPatterns
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
            array $vendorIncludePathPrefixes = [],
            array $vendorExcludePathPrefixes = [],
            array $vendorExcludePathPatterns = [],
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

                if ($this->isVendorPath($relativePath)) {
                    $vendorExplicitlyIncluded = $this->matchesPathPrefixes($relativePath, $vendorIncludePathPrefixes);

                    if (!$vendorExplicitlyIncluded) {
                        if ($this->matchesPathPrefixes($relativePath, $vendorExcludePathPrefixes)) {
                            continue;
                        }

                        if ($this->matchesPatterns($relativePath, $vendorExcludePathPatterns)) {
                            continue;
                        }

                        if ($this->matchesPatterns($relativePath, $dependencyExcludePathPatterns)) {
                            continue;
                        }

                        if ($this->matchesDefaultVendorExcludePatterns($relativePath)) {
                            continue;
                        }
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
                $prefix = trim(str_replace('\\', '/', (string) $prefix));
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
                $ext = trim((string) $ext);
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

        private function isVendorPath(string $relativePath): bool
        {
            return str_starts_with($relativePath, 'vendor/');
        }

        private function matchesDefaultVendorExcludePatterns(string $relativePath): bool
        {
            static $patterns = [
                '(^|/)tests?/',
                '(^|/)test-cases?/',
                '(^|/)examples?/',
                '(^|/)docs?/',
                '(^|/)doc/',
                '(^|/)benchmark(s)?/',
                '(^|/)fixtures?/',
                '(^|/)mocks?/',
                '(^|/)stubs?/',
                '(^|/)samples?/',
                '(^|/)demos?/',
                '(^|/)cases?/',
                '(^|/)specs?/',
                '(^|/)vendor-bin/',
                '(^|/)build/',
                '(^|/)tools?/',
                '(^|/)bin/',
                '(^|/)scripts?/',
                '^vendor/phpunit/',
                '^vendor/phpstan/',
                '^vendor/friendsofphp/php-cs-fixer/',
                '^vendor/squizlabs/php_codesniffer/',
                '^vendor/php-parallel-lint/',
                '^vendor/mockery/',
                '^vendor/behat/',
                '^vendor/pestphp/',
                '^vendor/infection/',
                '^vendor/vimeo/psalm/',
                '^vendor/phan/',
                '^vendor/sebastian/',
                '^vendor/theseer/',
                '^vendor/phar-io/',
                '^vendor/myclabs/deep-copy/',
                '^vendor/composer/installed\.php$',
                '^vendor/composer/installed\.json$',
                '^vendor/composer/autoload_classmap\.php$',
                '^vendor/composer/autoload_files\.php$',
                '^vendor/composer/autoload_namespaces\.php$',
                '^vendor/composer/autoload_psr4\.php$',
                '^vendor/composer/autoload_real\.php$',
                '^vendor/composer/autoload_static\.php$',
                '^vendor/composer/platform_check\.php$',
            ];

            return $this->matchesPatterns($relativePath, $patterns);
        }
    }