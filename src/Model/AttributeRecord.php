<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Model;

    final class AttributeRecord
    {
        /**
         * @param list<string> $args
         */
        public function __construct(
            public readonly string $name,
            public readonly array $args = [],
        ) {
        }

        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'args' => $this->args,
            ];
        }
    }