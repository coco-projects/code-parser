<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Model;

    final class ExtractionResult
    {
        /**
         * @param list<FileRecord> $files
         * @param list<SymbolRecord> $symbols
         * @param list<RelationRecord> $relations
         */
        public function __construct(
            public readonly array $files = [],
            public readonly array $symbols = [],
            public readonly array $relations = [],
        ) {
        }

        /**
         * @return list<array<string, mixed>>
         */
        public function allRecords(): array
        {
            $records = [];

            foreach ($this->files as $file) {
                $records[] = $file->toArray();
            }

            foreach ($this->symbols as $symbol) {
                $records[] = $symbol->toArray();
            }

            foreach ($this->relations as $relation) {
                $records[] = $relation->toArray();
            }

            return $records;
        }
    }