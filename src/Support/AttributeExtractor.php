<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Support;

    use Coco\codeParser\Model\AttributeRecord;
    use PhpParser\Node;
    use PhpParser\Node\Attribute;
    use PhpParser\Node\AttributeGroup;
    use PhpParser\PrettyPrinter\Standard;

    final class AttributeExtractor
    {
        private Standard $prettyPrinter;

        public function __construct()
        {
            $this->prettyPrinter = new Standard();
        }

        /**
         * @param list<AttributeGroup> $attributeGroups
         * @return list<array{name: string, args: list<string>}>
         */
        public function extract(array $attributeGroups): array
        {
            $result = [];

            foreach ($attributeGroups as $group) {
                foreach ($group->attrs as $attribute) {
                    $result[] = $this->extractSingle($attribute)->toArray();
                }
            }

            return $result;
        }

        private function extractSingle(Attribute $attribute): AttributeRecord
        {
            $args = [];

            foreach ($attribute->args as $arg) {
                $args[] = $this->prettyPrinter->prettyPrintExpr($arg->value);
            }

            return new AttributeRecord(
                name: $attribute->name->toString(),
                args: $args,
            );
        }
    }