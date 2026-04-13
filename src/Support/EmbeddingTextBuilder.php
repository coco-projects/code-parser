<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Support;

    use Coco\codeParser\Model\SymbolRecord;

    final class EmbeddingTextBuilder
    {
        public static function buildForSymbol(SymbolRecord $symbol): string
        {
            $lines = [];

            $lines[] = '[KIND] ' . $symbol->kind;
            $lines[] = '[FQNAME] ' . $symbol->fqname;
            $lines[] = '[FILE] ' . $symbol->relativePath . ':' . $symbol->startLine . '-' . $symbol->endLine;

            if ($symbol->namespace !== null) {
                $lines[] = '[NAMESPACE] ' . $symbol->namespace;
            }

            if ($symbol->className !== null) {
                $lines[] = '[CLASS] ' . $symbol->className;
            }

            if ($symbol->visibility !== null) {
                $lines[] = '[VISIBILITY] ' . $symbol->visibility;
            }

            if ($symbol->isStatic) {
                $lines[] = '[STATIC] true';
            }

            if ($symbol->isAbstract) {
                $lines[] = '[ABSTRACT] true';
            }

            if ($symbol->isFinal) {
                $lines[] = '[FINAL] true';
            }

            if ($symbol->extends !== null) {
                $lines[] = '[EXTENDS] ' . $symbol->extends;
            }

            if ($symbol->implements !== []) {
                $lines[] = '[IMPLEMENTS] ' . implode(', ', $symbol->implements);
            }

            if ($symbol->traits !== []) {
                $lines[] = '[TRAITS] ' . implode(', ', $symbol->traits);
            }

            if ($symbol->signature !== null) {
                $lines[] = '';
                if ($symbol->attributes !== []) {
                    $lines[] = '';
                    $lines[] = '[ATTRIBUTES]';
                    foreach ($symbol->attributes as $attribute) {
                        $args = $attribute['args'] ?? [];
                        $argText = $args !== [] ? '(' . implode(', ', $args) . ')' : '';
                        $lines[] = '- ' . $attribute['name'] . $argText;
                    }
                }
                if ($symbol->declaredType !== null) {
                    $lines[] = '';
                    $lines[] = '[DECLARED_TYPE]';
                    $lines[] = $symbol->declaredType;
                }

                if ($symbol->value !== null) {
                    $lines[] = '';
                    $lines[] = '[VALUE]';
                    if (is_scalar($symbol->value)) {
                        $lines[] = (string) $symbol->value;
                    } else {
                        $lines[] = json_encode($symbol->value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '(unserializable)';
                    }
                }
                $lines[] = '[SIGNATURE]';
                $lines[] = $symbol->signature;
            }

            if ($symbol->docblock !== null) {
                if ($symbol->docblock->summary !== null) {
                    $lines[] = '';
                    $lines[] = '[DOCBLOCK_SUMMARY]';
                    $lines[] = $symbol->docblock->summary;
                }

                if ($symbol->docblock->description !== '') {
                    $lines[] = '';
                    $lines[] = '[DOCBLOCK_DESCRIPTION]';
                    $lines[] = $symbol->docblock->description;
                }

                if ($symbol->docblock->params !== []) {
                    $lines[] = '';
                    $lines[] = '[DOCBLOCK_PARAMS]';
                    foreach ($symbol->docblock->params as $param) {
                        $lines[] = sprintf(
                            '- %s: %s %s',
                            $param['name'],
                            $param['type'] ?? '(unknown)',
                            trim($param['description'])
                        );
                    }
                }

                if ($symbol->docblock->returnType !== null) {
                    $lines[] = '';
                    $lines[] = '[DOCBLOCK_RETURN]';
                    $lines[] = $symbol->docblock->returnType;
                }

                if ($symbol->docblock->throws !== []) {
                    $lines[] = '';
                    $lines[] = '[DOCBLOCK_THROWS]';
                    foreach ($symbol->docblock->throws as $throwType) {
                        $lines[] = '- ' . $throwType;
                    }
                }

                if ($symbol->docblock->isDeprecated) {
                    $lines[] = '';
                    $lines[] = '[DOCBLOCK_DEPRECATED]';
                    $lines[] = 'true';
                }
            }

            if ($symbol->parameters !== []) {
                $lines[] = '';
                $lines[] = '[PARAMETERS]';
                foreach ($symbol->parameters as $param) {
                    $nativeType = $param['native_type'] ?? null;
                    $docblockType = $param['docblock_type'] ?? null;
                    $description = trim($param['description'] ?? '');

                    $typeText = [];
                    if ($nativeType !== null) {
                        $typeText[] = 'native=' . $nativeType;
                    }
                    if ($docblockType !== null) {
                        $typeText[] = 'doc=' . $docblockType;
                    }

                    $suffix = $typeText !== [] ? ' [' . implode(', ', $typeText) . ']' : '';
                    $desc = $description !== '' ? ' ' . $description : '';

                    $lines[] = '- ' . $param['name'] . $suffix . $desc;
                }
            }

            $lines[] = '';
            $lines[] = '[CODE]';
            $lines[] = $symbol->code;

            return implode(PHP_EOL, $lines);
        }
    }