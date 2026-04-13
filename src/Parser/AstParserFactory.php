<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Parser;

    use PhpParser\Error;
    use PhpParser\Node;
    use PhpParser\NodeTraverser;
    use PhpParser\NodeVisitor\NameResolver;
    use PhpParser\Parser;
    use PhpParser\ParserFactory;

    final class AstParserFactory
    {
        private Parser $parser;

        public function __construct()
        {
            $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        }

        public function parseFile(string $filePath): ParsedFile
        {
            $code = file_get_contents($filePath);

            if ($code === false) {
                return new ParsedFile(
                    filePath: $filePath,
                    code: '',
                    ast: null,
                    errors: [],
                    tokens: [],
                );
            }

            try {
                /** @var array<int, Node>|null $ast */
                $ast = $this->parser->parse($code);

                return new ParsedFile(
                    filePath: $filePath,
                    code: $code,
                    ast: $ast,
                    errors: [],
                    tokens: [],
                );
            } catch (Error $e) {
                return new ParsedFile(
                    filePath: $filePath,
                    code: $code,
                    ast: null,
                    errors: [$e],
                    tokens: [],
                );
            }
        }

        public function createTraverser(array $visitors = []): NodeTraverser
        {
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());

            foreach ($visitors as $visitor) {
                $traverser->addVisitor($visitor);
            }

            return $traverser;
        }
    }