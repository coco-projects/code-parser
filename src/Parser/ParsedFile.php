<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Parser;

    use PhpParser\Error;
    use PhpParser\Node;

    final class ParsedFile
    {
        /**
         * @param array<int, Node>|null $ast
         * @param list<Error> $errors
         * @param array<int, mixed> $tokens
         */
        public function __construct(
            public readonly string $filePath,
            public readonly string $code,
            public readonly ?array $ast,
            public readonly array $errors = [],
            public readonly array $tokens = [],
        ) {
        }

        public function isSuccess(): bool
        {
            return $this->ast !== null && $this->errors === [];
        }
    }