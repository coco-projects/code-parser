<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Model;

    final class DocblockRecord
    {
        /**
         * @param list<array{name: string, type: ?string, description: string}> $params
         * @param list<string> $throws
         * @param list<string> $tags
         */
        public function __construct(
            public readonly ?string $summary = null,
            public readonly string $description = '',
            public readonly array $params = [],
            public readonly ?string $returnType = null,
            public readonly array $throws = [],
            public readonly bool $isDeprecated = false,
            public readonly array $tags = [],
        ) {
        }

        public function isEmpty(): bool
        {
            return $this->summary === null
                   && $this->description === ''
                   && $this->params === []
                   && $this->returnType === null
                   && $this->throws === []
                   && $this->isDeprecated === false
                   && $this->tags === [];
        }

        public function toArray(): array
        {
            return [
                'summary' => $this->summary,
                'description' => $this->description,
                'params' => $this->params,
                'return_type' => $this->returnType,
                'throws' => $this->throws,
                'is_deprecated' => $this->isDeprecated,
                'tags' => $this->tags,
            ];
        }
    }