<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Support;

    use Coco\codeParser\Model\DocblockRecord;
    use PHPStan\PhpDocParser\Ast\PhpDoc\DeprecatedTagValueNode;
    use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
    use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
    use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
    use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
    use PHPStan\PhpDocParser\Lexer\Lexer;
    use PHPStan\PhpDocParser\Parser\ConstExprParser;
    use PHPStan\PhpDocParser\Parser\PhpDocParser;
    use PHPStan\PhpDocParser\Parser\TokenIterator;
    use PHPStan\PhpDocParser\Parser\TypeParser;

    final class DocblockParser
    {
        private Lexer $lexer;
        private PhpDocParser $parser;

        public function __construct()
        {
            $this->lexer = new Lexer();

            $constExprParser = new ConstExprParser();
            $typeParser = new TypeParser($constExprParser);
            $this->parser = new PhpDocParser($typeParser, $constExprParser);
        }

        public function parse(?string $rawDocComment): ?DocblockRecord
        {
            if ($rawDocComment === null || trim($rawDocComment) === '') {
                return null;
            }

            $summary = $this->extractSummary($rawDocComment);
            $description = $this->extractDescription($rawDocComment);

            $tokens = new TokenIterator($this->lexer->tokenize($rawDocComment));
            $phpDocNode = $this->parser->parse($tokens);

            $params = $this->extractParams($phpDocNode);
            $returnType = $this->extractReturnType($phpDocNode);
            $throws = $this->extractThrows($phpDocNode);
            $isDeprecated = $this->extractDeprecated($phpDocNode);
            $tags = $this->extractTagNames($phpDocNode);

            $record = new DocblockRecord(
                summary: $summary,
                description: $description,
                params: $params,
                returnType: $returnType,
                throws: $throws,
                isDeprecated: $isDeprecated,
                tags: $tags,
            );

            return $record->isEmpty() ? null : $record;
        }

        private function extractSummary(string $raw): ?string
        {
            $lines = $this->normalizeLines($raw);
            $textLines = $this->stripDocMarkers($lines);

            foreach ($textLines as $line) {
                if ($line === '') {
                    continue;
                }

                if (str_starts_with($line, '@')) {
                    return null;
                }

                return $line;
            }

            return null;
        }

        private function extractDescription(string $raw): string
        {
            $lines = $this->normalizeLines($raw);
            $textLines = $this->stripDocMarkers($lines);

            $seenSummary = false;
            $descriptionLines = [];

            foreach ($textLines as $line) {
                if (!$seenSummary) {
                    if ($line === '') {
                        continue;
                    }

                    if (str_starts_with($line, '@')) {
                        return '';
                    }

                    $seenSummary = true;
                    continue;
                }

                if (str_starts_with($line, '@')) {
                    break;
                }

                $descriptionLines[] = $line;
            }

            return trim(implode("\n", $descriptionLines));
        }

        /**
         * @return list<array{name: string, type: ?string, description: string}>
         */
        private function extractParams(PhpDocNode $phpDocNode): array
        {
            $result = [];

            foreach ($phpDocNode->getParamTagValues() as $paramTagValue) {
                if (!$paramTagValue instanceof ParamTagValueNode) {
                    continue;
                }

                $result[] = [
                    'name' => ltrim($paramTagValue->parameterName, '$'),
                    'type' => (string) $paramTagValue->type,
                    'description' => trim($paramTagValue->description),
                ];
            }

            return $result;
        }

        private function extractReturnType(PhpDocNode $phpDocNode): ?string
        {
            foreach ($phpDocNode->getReturnTagValues() as $returnTag) {
                if ($returnTag instanceof ReturnTagValueNode) {
                    return (string) $returnTag->type;
                }
            }

            return null;
        }

        /**
         * @return list<string>
         */
        private function extractThrows(PhpDocNode $phpDocNode): array
        {
            $result = [];

            foreach ($phpDocNode->getThrowsTagValues() as $throwsTag) {
                if ($throwsTag instanceof ThrowsTagValueNode) {
                    $result[] = (string) $throwsTag->type;
                }
            }

            return $result;
        }

        private function extractDeprecated(PhpDocNode $phpDocNode): bool
        {
            foreach ($phpDocNode->children as $child) {
                $value = $child->value ?? null;
                if ($value instanceof DeprecatedTagValueNode) {
                    return true;
                }
            }

            return false;
        }

        /**
         * @return list<string>
         */
        private function extractTagNames(PhpDocNode $phpDocNode): array
        {
            $tags = [];

            foreach ($phpDocNode->children as $child) {
                $name = $child->name ?? null;
                if (is_string($name)) {
                    $tags[] = ltrim($name, '@');
                }
            }

            $tags = array_values(array_unique($tags));
            sort($tags);

            return $tags;
        }

        /**
         * @return list<string>
         */
        private function normalizeLines(string $raw): array
        {
            $lines = preg_split('/\R/', $raw);

            if ($lines === false) {
                return [];
            }

            return array_map(
                static fn(string $line): string => rtrim($line),
                $lines
            );
        }

        /**
         * @param list<string> $lines
         * @return list<string>
         */
        private function stripDocMarkers(array $lines): array
        {
            $result = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '/**' || $line === '*/') {
                    continue;
                }

                if (str_starts_with($line, '*')) {
                    $line = ltrim(substr($line, 1));
                }

                $result[] = $line;
            }

            return $result;
        }
    }