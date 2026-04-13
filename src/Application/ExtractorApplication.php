<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Application;

    use Coco\codeParser\Bridge\RedisExtractionWriter;
    use Coco\codeParser\Config\ExtractorConfig;
    use Coco\codeParser\Config\RedisExtractionPayload;
    use Coco\codeParser\Model\ExtractionResult;
    use Coco\codeParser\Model\FileRecord;
    use Coco\codeParser\Parser\AstParserFactory;
    use Coco\codeParser\Scanner\PhpFileScanner;
    use Coco\codeParser\Serializer\JsonlWriter;
    use Coco\codeParser\Visitor\FileContextVisitor;
    use Coco\codeParser\Visitor\RelationCollectingVisitor;
    use Coco\codeParser\Visitor\SymbolCollectingVisitor;

    final class ExtractorApplication
    {
        public function run(ExtractorConfig $config, ?RedisExtractionPayload $payload = null): void
        {
            $projectRoot = rtrim(str_replace('\\', '/', $config->projectRoot), '/');
            $outputPath = $config->outputPath;

            $scanner = new PhpFileScanner();
            $files = $scanner->scan(
                projectRoot: $projectRoot,
                includePathPrefixes: $config->includePathPrefixes,
                allowedExtensions: $config->allowedExtensions,
                excludePathPrefixes: $config->excludePathPrefixes,
                excludePathPatterns: $config->excludePathPatterns,
                dependencyPathPrefixes: $config->dependencyPathPrefixes,
                dependencySourceSubdirs: $config->dependencySourceSubdirs,
                dependencyExcludePathPatterns: $config->dependencyExcludePathPatterns,
            );

            $redisWriter = null;
            if ($payload !== null && $payload->writeRedis) {
                $redisWriter = new RedisExtractionWriter($payload);
                $redisWriter->startTask(count($files));
            }

            $parserFactory = new AstParserFactory();

            $allFileRecords = [];
            $allSymbolRecords = [];
            $allRelationRecords = [];

            try {
                foreach ($files as $filePath) {
                    $relativePath = ltrim(substr(str_replace('\\', '/', $filePath), strlen($projectRoot)), '/');
                    $pathRole = $this->detectPathRole($relativePath, $config);

                    fwrite(STDOUT, "[EXTRACT] {$relativePath}\n");

                    $parsedFile = $parserFactory->parseFile($filePath);

                    if ($parsedFile->ast === null) {
                        fwrite(STDOUT, "[SKIP] {$relativePath} parse failed or ast is null\n");

                        if ($redisWriter !== null) {
                            $redisWriter->appendFileRecord($relativePath, [
                                'relative_path' => $relativePath,
                                'file_path' => $filePath,
                                'status' => 'skipped',
                                'error' => 'parse failed or ast is null',
                                'file_record' => null,
                                'symbol_records' => [],
                                'relation_records' => [],
                            ]);
                            $redisWriter->markSkipped();
                        }

                        continue;
                    }

                    try {
                        $fileContextVisitor = new FileContextVisitor();
                        $symbolCollectingVisitor = new SymbolCollectingVisitor(
                            filePath: $filePath,
                            relativePath: $relativePath,
                            sourceCode: $parsedFile->code,
                        );

                        $traverser = $parserFactory->createTraverser([
                            $fileContextVisitor,
                            $symbolCollectingVisitor,
                        ]);
                        $traverser->traverse($parsedFile->ast);

                        $symbols = $symbolCollectingVisitor->getSymbols();

                        $fileRecord = $fileContextVisitor->toFileRecord($filePath, $relativePath);

                        $symbolFqnames = [];
                        foreach ($symbols as $symbol) {
                            $symbolFqnames[] = $symbol->fqname;
                        }

                        $fileRecord = new FileRecord(
                            id: $fileRecord->id,
                            language: $fileRecord->language,
                            filePath: $fileRecord->filePath,
                            relativePath: $fileRecord->relativePath,
                            namespace: $fileRecord->namespace,
                            imports: $fileRecord->imports,
                            symbols: $symbolFqnames,
                            strictTypes: $this->detectStrictTypes($parsedFile->code),
                            declaredSymbols: $symbolFqnames,
                            pathRole: $pathRole,
                        );

                        $relationCollector = new RelationCollectingVisitor();
                        $relations = $relationCollector->collect($fileRecord, $symbols);

                        $allFileRecords[] = $fileRecord;

                        foreach ($symbols as $symbol) {
                            $allSymbolRecords[] = $symbol;
                        }

                        foreach ($relations as $relation) {
                            $allRelationRecords[] = $relation;
                        }

                        if ($redisWriter !== null) {
                            $redisWriter->appendFileRecord($relativePath, [
                                'relative_path' => $relativePath,
                                'file_path' => $filePath,
                                'status' => 'ok',
                                'error' => null,
                                'file_record' => $fileRecord->toArray(),
                                'symbol_records' => array_map(
                                    static fn($item): array => $item->toArray(),
                                    $symbols
                                ),
                                'relation_records' => array_map(
                                    static fn($item): array => $item->toArray(),
                                    $relations
                                ),
                            ]);
                            $redisWriter->markSuccess();
                        }
                    } catch (\Throwable $e) {
                        fwrite(STDOUT, "[SKIP] {$relativePath} " . $e->getMessage() . "\n");
                        if ($redisWriter !== null) {
                            $redisWriter->appendFileRecord($relativePath, [
                                'relative_path' => $relativePath,
                                'file_path' => $filePath,
                                'status' => 'skipped',
                                'error' => $e->getMessage(),
                                'file_record' => null,
                                'symbol_records' => [],
                                'relation_records' => [],
                            ]);
                            $redisWriter->markSkipped();
                        }

                        continue;
                    }
                }

                if ($payload === null || $payload->writeOutputFile) {
                    $result = new ExtractionResult(
                        files: $allFileRecords,
                        symbols: $allSymbolRecords,
                        relations: $allRelationRecords,
                    );

                    $writer = new JsonlWriter();
                    $writer->writeToFile($outputPath, $result->allRecords());
                }

                if ($redisWriter !== null) {
                    $redisWriter->complete('completed');
                }
            } catch (\Throwable $e) {
                if ($redisWriter !== null) {
                    $redisWriter->fail($e->getMessage());
                }
                throw $e;
            }
        }
        private function detectPathRole(string $relativePath, ExtractorConfig $config): string
        {
            $normalized = str_replace('\\', '/', $relativePath);

            foreach ($config->excludePathPatterns as $pattern) {
                if (!is_string($pattern) || trim($pattern) === '') {
                    continue;
                }

                if (@preg_match('#' . $pattern . '#i', $normalized) === 1) {
                    return $this->mapPatternToRole($normalized);
                }
            }

            foreach ($config->dependencyExcludePathPatterns as $pattern) {
                if (!is_string($pattern) || trim($pattern) === '') {
                    continue;
                }

                if (@preg_match('#' . $pattern . '#i', $normalized) === 1) {
                    return $this->mapPatternToRole($normalized);
                }
            }

            foreach ($config->dependencyPathPrefixes as $prefix) {
                $prefix = trim(str_replace('\\', '/', $prefix));
                if ($prefix !== '' && str_starts_with($normalized, $prefix)) {
                    return 'dependency';
                }
            }

            return 'project';
        }

        private function mapPatternToRole(string $relativePath): string
        {
            $path = strtolower($relativePath);

            if (preg_match('#(^|/)tests?/#', $path) === 1) {
                return 'test';
            }

            if (preg_match('#(^|/)examples?/#', $path) === 1) {
                return 'example';
            }

            if (preg_match('#(^|/)docs?/#', $path) === 1) {
                return 'doc';
            }

            if (preg_match('#(^|/)fixtures?/#', $path) === 1) {
                return 'fixture';
            }

            if (preg_match('#(^|/)mocks?/#', $path) === 1) {
                return 'mock';
            }

            if (preg_match('#(^|/)stubs?/#', $path) === 1) {
                return 'stub';
            }

            if (preg_match('#(^|/)benchmark(s)?/#', $path) === 1) {
                return 'benchmark';
            }

            return 'other';
        }
        private function detectStrictTypes(string $code): bool
        {
            return preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/i', $code) === 1;
        }
    }