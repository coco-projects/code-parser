<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Support;

    final class IdGenerator
    {
        public static function fileId(string $relativePath): string
        {
            return 'file_' . sha1($relativePath);
        }

        public static function symbolId(
            string $relativePath,
            string $kind,
            string $fqname,
            int $startLine,
            int $endLine
        ): string {
            return 'symbol_' . sha1($relativePath . '|' . $kind . '|' . $fqname . '|' . $startLine . '|' . $endLine);
        }

        public static function relationId(
            string $relationType,
            string $fromId,
            ?string $toSymbol
        ): string {
            return 'relation_' . sha1($relationType . '|' . $fromId . '|' . ($toSymbol ?? 'null'));
        }
    }