<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Model;

    final class RelationRecord
    {
        public function __construct(
            public readonly string $id,
            public readonly string $relationType,
            public readonly string $fromId,
            public readonly string $fromSymbol,
            public readonly ?string $toId,
            public readonly ?string $toSymbol,
            public readonly string $filePath,
            public readonly array $metadata = [],
            public readonly string $recordType = 'relation',
        ) {
        }

        public function toArray(): array
        {
            return [
                'record_type' => $this->recordType,
                'id' => $this->id,
                'relation_type' => $this->relationType,
                'from_id' => $this->fromId,
                'from_symbol' => $this->fromSymbol,
                'to_id' => $this->toId,
                'to_symbol' => $this->toSymbol,
                'file_path' => $this->filePath,
                'metadata' => $this->metadata,
            ];
        }
    }