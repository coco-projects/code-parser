<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Support;

    use PhpParser\Node;
    use PhpParser\Node\ComplexType;
    use PhpParser\Node\Identifier;
    use PhpParser\Node\IntersectionType;
    use PhpParser\Node\Name;
    use PhpParser\Node\NullableType;
    use PhpParser\Node\UnionType;

    final class TypeHelper
    {
        public static function typeToString(null|Node $type): ?string
        {
            if ($type === null) {
                return null;
            }

            if ($type instanceof NullableType) {
                $inner = self::typeToString($type->type);
                return $inner !== null ? '?' . $inner : null;
            }

            if ($type instanceof UnionType) {
                $parts = [];
                foreach ($type->types as $subType) {
                    $parts[] = self::typeToString($subType) ?? 'unknown';
                }

                return implode('|', $parts);
            }

            if ($type instanceof IntersectionType) {
                $parts = [];
                foreach ($type->types as $subType) {
                    $parts[] = self::typeToString($subType) ?? 'unknown';
                }

                return implode('&', $parts);
            }

            if ($type instanceof Identifier || $type instanceof Name) {
                return $type->toString();
            }

            if ($type instanceof ComplexType && method_exists($type, 'toString')) {
                return $type->toString();
            }

            if (method_exists($type, 'toString')) {
                return $type->toString();
            }

            return $type->getType();
        }
    }