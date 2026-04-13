<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Model;

    final class SymbolRecord
    {
        /**
         * @param list<array{name: string, type: ?string, byRef: bool, variadic: bool, default: ?string}> $parameters
         * @param list<string> $implements
         * @param list<string> $traits
         */
        public function __construct(
            public readonly string $id,
            public readonly string $kind,
            public readonly string $name,
            public readonly string $displayName,
            public readonly string $fqname,
            public readonly ?string $namespace,
            public readonly ?string $className,
            public readonly string $filePath,
            public readonly string $relativePath,
            public readonly int $startLine,
            public readonly int $endLine,
            public readonly ?string $visibility = null,
            public readonly bool $isStatic = false,
            public readonly bool $isAbstract = false,
            public readonly bool $isFinal = false,
            public readonly array $parameters = [],
            public readonly ?string $returnType = null,
            public readonly mixed $value = null,
            public readonly ?string $declaredType = null,
            public readonly ?string $docComment = null,
            public readonly ?DocblockRecord $docblock = null,
            public readonly array $attributes = [],
            public readonly ?string $signature = null,
            public readonly string $code = '',
            public readonly ?string $extends = null,
            public readonly array $implements = [],
            public readonly array $traits = [],
            public readonly ?string $embeddingText = null,
            public readonly string $pathRole = 'project',
            public readonly string $recordType = 'symbol',
        ) {
        }

        public function toArray(): array
        {
            return [
                'record_type'    => $this->recordType,
                'id'             => $this->id,
                'kind'           => $this->kind,
                'name'           => $this->name,
                'display_name'   => $this->displayName,
                'fqname'         => $this->fqname,
                'namespace'      => $this->namespace,
                'class_name'     => $this->className,
                'file_path'      => $this->filePath,
                'relative_path'  => $this->relativePath,
                'start_line'     => $this->startLine,
                'end_line'       => $this->endLine,
                'visibility'     => $this->visibility,
                'is_static'      => $this->isStatic,
                'is_abstract'    => $this->isAbstract,
                'is_final'       => $this->isFinal,
                'parameters'     => $this->parameters,
                'return_type'    => $this->returnType,
                'value' => $this->value,
                'declared_type' => $this->declaredType,
                'doc_comment'    => $this->docComment,
                'docblock'       => $this->docblock?->toArray(),
                'attributes'     => $this->attributes,
                'signature'      => $this->signature,
                'code'           => $this->code,
                'extends'        => $this->extends,
                'implements'     => $this->implements,
                'traits'         => $this->traits,
                'embedding_text' => $this->embeddingText,
                'path_role'      => $this->pathRole,
            ];
        }
    }