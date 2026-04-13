<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Model;

    final class FileRecord
    {
        /**
         * @param array<string, string> $imports
         * @param list<string> $symbols
         * @param list<string> $declaredSymbols
         */
        public function __construct(
            public readonly string $id,
            public readonly string $language,
            public readonly string $filePath,
            public readonly string $relativePath,
            public readonly ?string $namespace,
            public readonly array $imports = [],
            public readonly array $symbols = [],
            public readonly bool $strictTypes = false,
            public readonly array $declaredSymbols = [],
            public readonly string $pathRole = 'project',
            public readonly string $recordType = 'file',
        ) {
        }

        public function toArray(): array
        {
            return [
                'record_type' => $this->recordType,
                'id' => $this->id,
                'language' => $this->language,
                'file_path' => $this->filePath,
                'relative_path' => $this->relativePath,
                'namespace' => $this->namespace,
                'imports' => $this->imports,
                'symbols' => $this->symbols,
                'strict_types' => $this->strictTypes,
                'declared_symbols' => $this->declaredSymbols,
                'path_role' => $this->pathRole,
            ];
        }
    }