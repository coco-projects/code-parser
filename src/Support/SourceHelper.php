<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Support;

    use PhpParser\Node;

    final class SourceHelper
    {
        public static function extractCodeByNode(string $code, Node $node): string
        {
            $start = $node->getStartFilePos();
            $end = $node->getEndFilePos();

            if (!is_int($start) || !is_int($end) || $start < 0 || $end < $start) {
                return '';
            }

            return substr($code, $start, $end - $start + 1);
        }
    }