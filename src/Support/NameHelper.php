<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Support;

    use PhpParser\Node;
    use PhpParser\Node\Name;

    final class NameHelper
    {
        public static function resolvedName(Node $node): ?string
        {
            $namespacedName = $node->getAttribute('namespacedName');

            if ($namespacedName instanceof Name) {
                return $namespacedName->toString();
            }

            return null;
        }

        public static function nodeNameToString(null|Name $name): ?string
        {
            return $name?->toString();
        }
    }