<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Visitor;

    use Coco\codeParser\Model\FileRecord;
    use Coco\codeParser\Model\RelationRecord;
    use Coco\codeParser\Model\SymbolRecord;
    use Coco\codeParser\Support\IdGenerator;

    final class RelationCollectingVisitor
    {
        /**
         * @param FileRecord $fileRecord
         * @param list<SymbolRecord> $symbols
         * @return list<RelationRecord>
         */
        public function collect(FileRecord $fileRecord, array $symbols): array
        {
            $relations = [];

            foreach ($symbols as $symbol) {
                $relations[] = new RelationRecord(
                    id: IdGenerator::relationId('defined_in', $symbol->id, $fileRecord->id),
                    relationType: 'defined_in',
                    fromId: $symbol->id,
                    fromSymbol: $symbol->fqname,
                    toId: $fileRecord->id,
                    toSymbol: $fileRecord->relativePath,
                    filePath: $symbol->filePath,
                );

                if (
                    in_array($symbol->kind, ['method', 'property', 'class_const', 'enum_case'], true)
                    && $symbol->className !== null
                ) {
                    $containerSymbol = null;

                    foreach ($symbols as $candidate) {
                        if (
                            in_array($candidate->kind, ['class', 'interface', 'trait', 'enum'], true)
                            && $candidate->name === $symbol->className
                        ) {
                            $containerSymbol = $candidate;
                            break;
                        }
                    }

                    $relations[] = new RelationRecord(
                        id: IdGenerator::relationId('belongs_to', $symbol->id, $symbol->className),
                        relationType: 'belongs_to',
                        fromId: $symbol->id,
                        fromSymbol: $symbol->fqname,
                        toId: $containerSymbol?->id,
                        toSymbol: $containerSymbol?->fqname ?? $symbol->className,
                        filePath: $symbol->filePath,
                    );
                }

                if ($symbol->extends !== null) {
                    $relations[] = new RelationRecord(
                        id: IdGenerator::relationId('extends', $symbol->id, $symbol->extends),
                        relationType: 'extends',
                        fromId: $symbol->id,
                        fromSymbol: $symbol->fqname,
                        toId: null,
                        toSymbol: $symbol->extends,
                        filePath: $symbol->filePath,
                    );
                }

                foreach ($symbol->implements as $implemented) {
                    $relations[] = new RelationRecord(
                        id: IdGenerator::relationId('implements', $symbol->id, $implemented),
                        relationType: 'implements',
                        fromId: $symbol->id,
                        fromSymbol: $symbol->fqname,
                        toId: null,
                        toSymbol: $implemented,
                        filePath: $symbol->filePath,
                    );
                }

                foreach ($symbol->traits as $trait) {
                    $relations[] = new RelationRecord(
                        id: IdGenerator::relationId('uses_trait', $symbol->id, $trait),
                        relationType: 'uses_trait',
                        fromId: $symbol->id,
                        fromSymbol: $symbol->fqname,
                        toId: null,
                        toSymbol: $trait,
                        filePath: $symbol->filePath,
                    );
                }
            }

            return $relations;
        }
    }