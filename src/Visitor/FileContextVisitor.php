<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Visitor;

    use Coco\codeParser\Model\FileRecord;
    use Coco\codeParser\Support\IdGenerator;
    use PhpParser\Node;
    use PhpParser\Node\Stmt\Namespace_;
    use PhpParser\Node\Stmt\Use_;
    use PhpParser\NodeVisitorAbstract;

    final class FileContextVisitor extends NodeVisitorAbstract
    {
        private ?string $namespace = null;

        /**
         * @var array<string, string>
         */
        private array $imports = [];

        public function enterNode(Node $node)
        {
            if ($node instanceof Namespace_) {
                $this->namespace = $node->name?->toString();
            }

            if ($node instanceof Use_) {
                foreach ($node->uses as $useUse) {
                    $alias = $useUse->alias?->toString();

                    $fullName = $useUse->name->toString();
                    $shortName = $alias ?: $useUse->name->getLast();

                    $this->imports[$shortName] = $fullName;
                }
            }

            return null;
        }

        public function toFileRecord(string $filePath, string $relativePath): FileRecord
        {
            ksort($this->imports);

            return new FileRecord(
                id: IdGenerator::fileId($relativePath),
                language: 'php',
                filePath: $filePath,
                relativePath: $relativePath,
                namespace: $this->namespace,
                imports: $this->imports,
                symbols: [],
                strictTypes: false,
                declaredSymbols: [],
            );
        }
    }