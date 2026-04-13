<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Visitor;

    use Coco\codeParser\Model\SymbolRecord;
    use Coco\codeParser\Support\AttributeExtractor;
    use Coco\codeParser\Support\DocblockParser;
    use Coco\codeParser\Support\EmbeddingTextBuilder;
    use Coco\codeParser\Support\IdGenerator;
    use Coco\codeParser\Support\NameHelper;
    use Coco\codeParser\Support\SourceHelper;
    use Coco\codeParser\Support\TypeHelper;
    use PhpParser\Node;
    use PhpParser\Node\Param;
    use PhpParser\Node\Stmt\Class_;
    use PhpParser\Node\Stmt\ClassConst;
    use PhpParser\Node\Stmt\ClassMethod;
    use PhpParser\Node\Stmt\Enum_;
    use PhpParser\Node\Stmt\EnumCase;
    use PhpParser\Node\Stmt\Function_;
    use PhpParser\Node\Stmt\Interface_;
    use PhpParser\Node\Stmt\Namespace_;
    use PhpParser\Node\Stmt\Property;
    use PhpParser\Node\Stmt\Trait_;
    use PhpParser\Node\Stmt\TraitUse;
    use PhpParser\NodeVisitorAbstract;
    use PhpParser\PrettyPrinter\Standard;

    final class SymbolCollectingVisitor extends NodeVisitorAbstract
    {
        private ?string $namespace = null;
        private ?string $currentClass = null;

        /**
         * @var list<SymbolRecord>
         */
        private array $symbols = [];

        private Standard $prettyPrinter;
        private DocblockParser $docblockParser;
        private AttributeExtractor $attributeExtractor;

        public function __construct(
            private readonly string $filePath,
            private readonly string $relativePath,
            private readonly string $sourceCode,
        ) {
            $this->prettyPrinter = new Standard();
            $this->docblockParser = new DocblockParser();
            $this->attributeExtractor = new AttributeExtractor();
        }

        public function enterNode(Node $node)
        {
            if ($node instanceof Namespace_) {
                $this->namespace = $node->name?->toString();
            }

            if ($node instanceof Class_) {
                $className = $node->name?->toString() ?? '<anonymous-class>';
                $this->currentClass = $className;

                $fqname = NameHelper::resolvedName($node) ?? $this->buildFqname($className, null);
                $docComment = $this->extractDocComment($node);
                $docblock = $this->docblockParser->parse($docComment);

                $symbol = new SymbolRecord(
                    id: IdGenerator::symbolId(
                        $this->relativePath,
                        'class',
                        $fqname,
                        $node->getStartLine(),
                        $node->getEndLine()
                    ),
                    kind: 'class',
                    name: $className,
                    displayName: $this->buildDisplayName($className, null),
                    fqname: $fqname,
                    namespace: $this->namespace,
                    className: null,
                    filePath: $this->filePath,
                    relativePath: $this->relativePath,
                    startLine: $node->getStartLine(),
                    endLine: $node->getEndLine(),
                    visibility: null,
                    isStatic: false,
                    isAbstract: $node->isAbstract(),
                    isFinal: $node->isFinal(),
                    parameters: [],
                    returnType: null,
                    value: null,
                    declaredType: null,
                    docComment: $docComment,
                    docblock: $docblock,
                    attributes: $this->attributeExtractor->extract($node->attrGroups),
                    signature: $this->buildClassLikeSignature('class', $className, $node->isAbstract(), $node->isFinal()),
                    code: $this->extractNodeCode($node),
                    extends: $node->extends?->toString(),
                    implements: array_map(static fn($item) => $item->toString(), $node->implements),
                    traits: $this->extractTraits($node),
                );

                $this->symbols[] = $this->withEmbeddingText($symbol);
            }

            if ($node instanceof Interface_) {
                $name = $node->name->toString();
                $fqname = NameHelper::resolvedName($node) ?? $this->buildFqname($name, null);
                $docComment = $this->extractDocComment($node);
                $docblock = $this->docblockParser->parse($docComment);

                $symbol = new SymbolRecord(
                    id: IdGenerator::symbolId(
                        $this->relativePath,
                        'interface',
                        $fqname,
                        $node->getStartLine(),
                        $node->getEndLine()
                    ),
                    kind: 'interface',
                    name: $name,
                    displayName: $this->buildDisplayName($name, null),
                    fqname: $fqname,
                    namespace: $this->namespace,
                    className: null,
                    filePath: $this->filePath,
                    relativePath: $this->relativePath,
                    startLine: $node->getStartLine(),
                    endLine: $node->getEndLine(),
                    visibility: null,
                    isStatic: false,
                    isAbstract: false,
                    isFinal: false,
                    parameters: [],
                    returnType: null,
                    value: null,
                    declaredType: null,
                    docComment: $docComment,
                    docblock: $docblock,
                    attributes: $this->attributeExtractor->extract($node->attrGroups),
                    signature: 'interface ' . $name,
                    code: $this->extractNodeCode($node),
                    extends: null,
                    implements: array_map(static fn($item) => $item->toString(), $node->extends),
                    traits: [],
                );

                $this->symbols[] = $this->withEmbeddingText($symbol);
            }

            if ($node instanceof Trait_) {
                $name = $node->name->toString();
                $fqname = NameHelper::resolvedName($node) ?? $this->buildFqname($name, null);
                $docComment = $this->extractDocComment($node);
                $docblock = $this->docblockParser->parse($docComment);

                $symbol = new SymbolRecord(
                    id: IdGenerator::symbolId(
                        $this->relativePath,
                        'trait',
                        $fqname,
                        $node->getStartLine(),
                        $node->getEndLine()
                    ),
                    kind: 'trait',
                    name: $name,
                    displayName: $this->buildDisplayName($name, null),
                    fqname: $fqname,
                    namespace: $this->namespace,
                    className: null,
                    filePath: $this->filePath,
                    relativePath: $this->relativePath,
                    startLine: $node->getStartLine(),
                    endLine: $node->getEndLine(),
                    visibility: null,
                    isStatic: false,
                    isAbstract: false,
                    isFinal: false,
                    parameters: [],
                    returnType: null,
                    value: null,
                    declaredType: null,
                    docComment: $docComment,
                    docblock: $docblock,
                    attributes: $this->attributeExtractor->extract($node->attrGroups),
                    signature: 'trait ' . $name,
                    code: $this->extractNodeCode($node),
                    extends: null,
                    implements: [],
                    traits: [],
                );

                $this->symbols[] = $this->withEmbeddingText($symbol);
            }

            if ($node instanceof Enum_) {
                $name = $node->name->toString();
                $fqname = NameHelper::resolvedName($node) ?? $this->buildFqname($name, null);
                $docComment = $this->extractDocComment($node);
                $docblock = $this->docblockParser->parse($docComment);
                $this->currentClass = $name;

                $symbol = new SymbolRecord(
                    id: IdGenerator::symbolId(
                        $this->relativePath,
                        'enum',
                        $fqname,
                        $node->getStartLine(),
                        $node->getEndLine()
                    ),
                    kind: 'enum',
                    name: $name,
                    displayName: $this->buildDisplayName($name, null),
                    fqname: $fqname,
                    namespace: $this->namespace,
                    className: null,
                    filePath: $this->filePath,
                    relativePath: $this->relativePath,
                    startLine: $node->getStartLine(),
                    endLine: $node->getEndLine(),
                    visibility: null,
                    isStatic: false,
                    isAbstract: false,
                    isFinal: false,
                    parameters: [],
                    returnType: null,
                    value: null,
                    declaredType: null,
                    docComment: $docComment,
                    docblock: $docblock,
                    attributes: $this->attributeExtractor->extract($node->attrGroups),
                    signature: 'enum ' . $name,
                    code: $this->extractNodeCode($node),
                    extends: null,
                    implements: [],
                    traits: [],
                );

                $this->symbols[] = $this->withEmbeddingText($symbol);
            }

            if ($node instanceof Function_) {
                $name = $node->name->toString();
                $fqname = NameHelper::resolvedName($node) ?? $this->buildFqname($name, null);

                $docComment = $this->extractDocComment($node);
                $docblock = $this->docblockParser->parse($docComment);
                $params = $this->extractParameters($node->getParams(), $docblock?->params);
                $returnType = $this->resolveDocblockReturnType(
                    $docblock,
                    TypeHelper::typeToString($node->getReturnType())
                );

                $symbol = new SymbolRecord(
                    id: IdGenerator::symbolId(
                        $this->relativePath,
                        'function',
                        $fqname,
                        $node->getStartLine(),
                        $node->getEndLine()
                    ),
                    kind: 'function',
                    name: $name,
                    displayName: $this->buildDisplayName($name, null),
                    fqname: $fqname,
                    namespace: $this->namespace,
                    className: null,
                    filePath: $this->filePath,
                    relativePath: $this->relativePath,
                    startLine: $node->getStartLine(),
                    endLine: $node->getEndLine(),
                    visibility: null,
                    isStatic: false,
                    isAbstract: false,
                    isFinal: false,
                    parameters: $params,
                    returnType: $returnType,
                    value: null,
                    declaredType: null,
                    docComment: $docComment,
                    docblock: $docblock,
                    attributes: $this->attributeExtractor->extract($node->attrGroups),
                    signature: $this->buildFunctionSignature($name, $params, $returnType),
                    code: $this->extractNodeCode($node),
                    extends: null,
                    implements: [],
                    traits: [],
                );

                $this->symbols[] = $this->withEmbeddingText($symbol);
            }

            if ($node instanceof Property) {
                foreach ($node->props as $prop) {
                    $propertyName = $prop->name->toString();
                    $fqname = $this->buildFqname($propertyName, $this->currentClass);

                    $docComment = $this->extractDocComment($node);
                    $docblock = $this->docblockParser->parse($docComment);

                    $symbol = new SymbolRecord(
                        id: IdGenerator::symbolId(
                            $this->relativePath,
                            'property',
                            $fqname,
                            $node->getStartLine(),
                            $node->getEndLine()
                        ),
                        kind: 'property',
                        name: $propertyName,
                        displayName: $this->buildDisplayName($propertyName, $this->currentClass),
                        fqname: $fqname,
                        namespace: $this->namespace,
                        className: $this->currentClass,
                        filePath: $this->filePath,
                        relativePath: $this->relativePath,
                        startLine: $node->getStartLine(),
                        endLine: $node->getEndLine(),
                        visibility: $this->extractPropertyVisibility($node),
                        isStatic: $node->isStatic(),
                        isAbstract: false,
                        isFinal: false,
                        parameters: [],
                        returnType: null,
                        value: $prop->default !== null ? $this->prettyPrintExpr($prop->default) : null,
                        declaredType: TypeHelper::typeToString($node->type),
                        docComment: $docComment,
                        docblock: $docblock,
                        attributes: $this->attributeExtractor->extract($node->attrGroups),
                        signature: $this->buildPropertySignature($node, $propertyName),
                        code: $this->extractNodeCode($node),
                        extends: null,
                        implements: [],
                        traits: [],
                    );

                    $this->symbols[] = $this->withEmbeddingText($symbol);
                }
            }

            if ($node instanceof ClassConst) {
                foreach ($node->consts as $const) {
                    $constName = $const->name->toString();
                    $fqname = $this->buildFqname($constName, $this->currentClass);

                    $docComment = $this->extractDocComment($node);
                    $docblock = $this->docblockParser->parse($docComment);

                    $symbol = new SymbolRecord(
                        id: IdGenerator::symbolId(
                            $this->relativePath,
                            'class_const',
                            $fqname,
                            $node->getStartLine(),
                            $node->getEndLine()
                        ),
                        kind: 'class_const',
                        name: $constName,
                        displayName: $this->buildDisplayName($constName, $this->currentClass),
                        fqname: $fqname,
                        namespace: $this->namespace,
                        className: $this->currentClass,
                        filePath: $this->filePath,
                        relativePath: $this->relativePath,
                        startLine: $node->getStartLine(),
                        endLine: $node->getEndLine(),
                        visibility: $this->extractConstVisibility($node),
                        isStatic: false,
                        isAbstract: false,
                        isFinal: $node->isFinal(),
                        parameters: [],
                        returnType: null,
                        value: $this->prettyPrintExpr($const->value),
                        declaredType: TypeHelper::typeToString($node->type),
                        docComment: $docComment,
                        docblock: $docblock,
                        attributes: $this->attributeExtractor->extract($node->attrGroups),
                        signature: $this->buildClassConstSignature($node, $constName),
                        code: $this->extractNodeCode($node),
                        extends: null,
                        implements: [],
                        traits: [],
                    );

                    $this->symbols[] = $this->withEmbeddingText($symbol);
                }
            }

            if ($node instanceof EnumCase) {
                $caseName = $node->name->toString();
                $fqname = $this->buildFqname($caseName, $this->currentClass);

                $docComment = $this->extractDocComment($node);
                $docblock = $this->docblockParser->parse($docComment);

                $symbol = new SymbolRecord(
                    id: IdGenerator::symbolId(
                        $this->relativePath,
                        'enum_case',
                        $fqname,
                        $node->getStartLine(),
                        $node->getEndLine()
                    ),
                    kind: 'enum_case',
                    name: $caseName,
                    displayName: $this->buildDisplayName($caseName, $this->currentClass),
                    fqname: $fqname,
                    namespace: $this->namespace,
                    className: $this->currentClass,
                    filePath: $this->filePath,
                    relativePath: $this->relativePath,
                    startLine: $node->getStartLine(),
                    endLine: $node->getEndLine(),
                    visibility: 'public',
                    isStatic: false,
                    isAbstract: false,
                    isFinal: false,
                    parameters: [],
                    returnType: null,
                    value: $node->expr !== null ? $this->prettyPrintExpr($node->expr) : null,
                    declaredType: null,
                    docComment: $docComment,
                    docblock: $docblock,
                    attributes: $this->attributeExtractor->extract($node->attrGroups),
                    signature: 'case ' . $caseName,
                    code: $this->extractNodeCode($node),
                    extends: null,
                    implements: [],
                    traits: [],
                );

                $this->symbols[] = $this->withEmbeddingText($symbol);
            }

            if ($node instanceof ClassMethod) {
                $name = $node->name->toString();
                $fqname = $this->buildMethodFqname($name, $this->currentClass);


                $methodMeta = $this->extractMethodMetadata($node);
                $docComment = $methodMeta['docComment'];
                $docblock = $this->docblockParser->parse($docComment);

                $params = $this->extractParameters($node->getParams(), $docblock?->params);
                $returnType = $this->resolveDocblockReturnType(
                    $docblock,
                    TypeHelper::typeToString($node->getReturnType())
                );

                $symbol = new SymbolRecord(
                    id: IdGenerator::symbolId(
                        $this->relativePath,
                        'method',
                        $fqname,
                        $node->getStartLine(),
                        $node->getEndLine()
                    ),
                    kind: 'method',
                    name: $name,
                    displayName: $this->buildDisplayName($name, $this->currentClass),
                    fqname: $fqname,
                    namespace: $this->namespace,
                    className: $this->currentClass,
                    filePath: $this->filePath,
                    relativePath: $this->relativePath,
                    startLine: $node->getStartLine(),
                    endLine: $node->getEndLine(),
                    visibility: $this->extractVisibility($node),
                    isStatic: $node->isStatic(),
                    isAbstract: $node->isAbstract(),
                    isFinal: false,
                    parameters: $params,
                    returnType: $returnType,
                    value: null,
                    declaredType: null,
                    docComment: $docComment,
                    docblock: $docblock,
                    attributes: $methodMeta['attributes'],
                    signature: $this->buildMethodSignature($name, $params, $returnType, $node),
                    code: $this->extractNodeCode($node),
                    extends: null,
                    implements: [],
                    traits: [],
                );

                $this->symbols[] = $this->withEmbeddingText($symbol);
            }

            return null;
        }

        public function leaveNode(Node $node)
        {
            if ($node instanceof Class_ || $node instanceof Enum_) {
                $this->currentClass = null;
            }

            return null;
        }

        /**
         * @return list<SymbolRecord>
         */
        public function getSymbols(): array
        {
            return $this->symbols;
        }

        private function withEmbeddingText(SymbolRecord $symbol): SymbolRecord
        {
            return new SymbolRecord(
                id: $symbol->id,
                kind: $symbol->kind,
                name: $symbol->name,
                displayName: $symbol->displayName,
                fqname: $symbol->fqname,
                namespace: $symbol->namespace,
                className: $symbol->className,
                filePath: $symbol->filePath,
                relativePath: $symbol->relativePath,
                startLine: $symbol->startLine,
                endLine: $symbol->endLine,
                visibility: $symbol->visibility,
                isStatic: $symbol->isStatic,
                isAbstract: $symbol->isAbstract,
                isFinal: $symbol->isFinal,
                parameters: $symbol->parameters,
                returnType: $symbol->returnType,
                value: $symbol->value,
                declaredType: $symbol->declaredType,
                docComment: $symbol->docComment,
                docblock: $symbol->docblock,
                attributes: $symbol->attributes,
                signature: $symbol->signature,
                code: $symbol->code,
                extends: $symbol->extends,
                implements: $symbol->implements,
                traits: $symbol->traits,
                embeddingText: EmbeddingTextBuilder::buildForSymbol($symbol),
            );
        }

        private function buildDisplayName(string $name, ?string $className): string
        {
            if ($className !== null) {
                if ($this->namespace !== null) {
                    return $this->namespace . '\\' . $className . '::' . $name;
                }

                return $className . '::' . $name;
            }

            if ($this->namespace !== null) {
                return $this->namespace . '\\' . $name;
            }

            return $name;
        }

        private function buildFqname(string $name, ?string $className): string
        {
            if ($className !== null) {
                if ($this->namespace !== null) {
                    return $this->namespace . '\\' . $className . '::' . $name;
                }

                return $className . '::' . $name;
            }

            if ($this->namespace !== null) {
                return $this->namespace . '\\' . $name;
            }

            return $name;
        }

        private function buildMethodFqname(string $methodName, ?string $className): string
        {
            return $this->buildFqname($methodName, $className);
        }

        private function extractVisibility(ClassMethod $node): string
        {
            if ($node->isPrivate()) {
                return 'private';
            }

            if ($node->isProtected()) {
                return 'protected';
            }

            return 'public';
        }

        private function extractPropertyVisibility(Property $node): string
        {
            if ($node->isPrivate()) {
                return 'private';
            }

            if ($node->isProtected()) {
                return 'protected';
            }

            return 'public';
        }

        private function extractConstVisibility(ClassConst $node): string
        {
            if ($node->isPrivate()) {
                return 'private';
            }

            if ($node->isProtected()) {
                return 'protected';
            }

            return 'public';
        }


        private function resolveDocblockReturnType(?\Coco\codeParser\Model\DocblockRecord $docblock, ?string $nativeReturnType): ?string
        {
            if ($nativeReturnType !== null) {
                return $nativeReturnType;
            }

            return $docblock?->returnType;
        }
        /**
         * @param array<int, Param> $params
         * @param ?array<int, array{name: string, type: ?string, description: string}> $docblockParams
         * @return list<array{
         *     name: string,
         *     native_type: ?string,
         *     docblock_type: ?string,
         *     description: string,
         *     byRef: bool,
         *     variadic: bool,
         *     default: ?string
         * }>
         */
        private function extractParameters(array $params, ?array $docblockParams = null): array
        {
            $result = [];
            $docblockMap = [];

            if ($docblockParams !== null) {
                foreach ($docblockParams as $item) {
                    $docblockMap[$item['name']] = $item;
                }
            }

            foreach ($params as $param) {
                $paramName = is_string($param->var->name) ? $param->var->name : (string) $param->var->name;
                $docblockItem = $docblockMap[$paramName] ?? null;

                $result[] = [
                    'name' => $paramName,
                    'native_type' => TypeHelper::typeToString($param->type),
                    'docblock_type' => $docblockItem['type'] ?? null,
                    'description' => $docblockItem['description'] ?? '',
                    'byRef' => $param->byRef,
                    'variadic' => $param->variadic,
                    'default' => $param->default ? $this->prettyPrintExpr($param->default) : null,
                ];
            }

            return $result;
        }

        private function prettyPrintExpr(Node $expr): string
        {
            return $this->prettyPrinter->prettyPrintExpr($expr);
        }

        private function buildClassLikeSignature(string $kind, string $name, bool $isAbstract, bool $isFinal): string
        {
            $parts = [];

            if ($isFinal) {
                $parts[] = 'final';
            }

            if ($isAbstract) {
                $parts[] = 'abstract';
            }

            $parts[] = $kind;
            $parts[] = $name;

            return implode(' ', $parts);
        }

        /**
         * @param list<array{name: string, type: ?string, byRef: bool, variadic: bool, default: ?string}> $params
         */
        private function buildFunctionSignature(string $name, array $params, ?string $returnType): string
        {
            $signature = 'function ' . $name . '(' . $this->buildParamString($params) . ')';

            if ($returnType !== null) {
                $signature .= ': ' . $returnType;
            }

            return $signature;
        }

        /**
         * @param list<array{name: string, type: ?string, byRef: bool, variadic: bool, default: ?string}> $params
         */
        private function buildMethodSignature(string $name, array $params, ?string $returnType, ClassMethod $node): string
        {
            $parts = [];
            $parts[] = $this->extractVisibility($node);

            if ($node->isStatic()) {
                $parts[] = 'static';
            }

            if ($node->isAbstract()) {
                $parts[] = 'abstract';
            }

            $parts[] = 'function';
            $parts[] = $name . '(' . $this->buildParamString($params) . ')';

            $signature = implode(' ', $parts);

            if ($returnType !== null) {
                $signature .= ': ' . $returnType;
            }

            return $signature;
        }

        private function buildPropertySignature(Property $node, string $propertyName): string
        {
            $parts = [];
            $parts[] = $this->extractPropertyVisibility($node);

            if ($node->isStatic()) {
                $parts[] = 'static';
            }

            if ($node->type !== null) {
                $parts[] = TypeHelper::typeToString($node->type);
            }

            $parts[] = '$' . $propertyName;

            return implode(' ', array_filter($parts, static fn($item) => $item !== null && $item !== ''));
        }

        private function buildClassConstSignature(ClassConst $node, string $constName): string
        {
            $parts = [];
            $parts[] = $this->extractConstVisibility($node);

            if ($node->isFinal()) {
                $parts[] = 'final';
            }

            $parts[] = 'const';

            if ($node->type !== null) {
                $parts[] = TypeHelper::typeToString($node->type);
            }

            $parts[] = $constName;

            return implode(' ', array_filter($parts, static fn($item) => $item !== null && $item !== ''));
        }


        /**
         * @param list<array{
         *     name: string,
         *     native_type: ?string,
         *     docblock_type: ?string,
         *     description: string,
         *     byRef: bool,
         *     variadic: bool,
         *     default: ?string
         * }> $params
         */
        private function buildParamString(array $params): string
        {
            $parts = [];

            foreach ($params as $param) {
                $segment = '';

                if ($param['native_type'] !== null) {
                    $segment .= $param['native_type'] . ' ';
                }

                if ($param['byRef']) {
                    $segment .= '&';
                }

                if ($param['variadic']) {
                    $segment .= '...';
                }

                $segment .= '$' . $param['name'];

                if ($param['default'] !== null) {
                    $segment .= ' = ' . $param['default'];
                }

                $parts[] = $segment;
            }

            return implode(', ', $parts);
        }
        /**
         * @return list<string>
         */
        private function extractTraits(Class_ $node): array
        {
            $traits = [];

            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof TraitUse) {
                    foreach ($stmt->traits as $trait) {
                        $traits[] = $trait->toString();
                    }
                }
            }

            return $traits;
        }

        private function extractNodeCode(Node $node): string
        {
            return SourceHelper::extractCodeByNode($this->sourceCode, $node);
        }

        private function extractDocComment(Node $node): ?string
        {
            $docComment = $node->getDocComment()?->getText();
            if ($docComment !== null) {
                return $docComment;
            }

            $comments = $node->getComments();
            foreach ($comments as $comment) {
                $text = $comment->getText();
                if (str_starts_with($text, '/**')) {
                    return $text;
                }
            }

            return null;
        }

        /**
         * @return array{docComment: ?string, attributes: list<array{name: string, args: list<string>}>}
         */
        private function extractMethodMetadata(ClassMethod $node): array
        {
            $docComment = $this->extractDocComment($node);
            $attributes = $this->attributeExtractor->extract($node->attrGroups);

            $rawCode = $this->extractNodeCode($node);

            if ($docComment === null && preg_match('#/\*\*(.*?)\*/#s', $rawCode, $matches) === 1) {
                $docComment = '/**' . $matches[1] . '*/';
            }

            if ($attributes === [] && preg_match('/#\[(.*?)\]/s', $rawCode, $matches) === 1) {
                $attributes = [
                    [
                        'name' => trim($matches[1], "\\ \t\n\r\0\x0B"),
                        'args' => [],
                    ],
                ];
            }

            return [
                'docComment' => $docComment,
                'attributes' => $attributes,
            ];
        }
    }