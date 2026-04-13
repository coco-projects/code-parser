<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Serializer;

    final class JsonlWriter
    {
        /**
         * @param iterable<array<string, mixed>> $records
         */
        public function writeToStdout(iterable $records, bool $pretty = false): void
        {
            foreach ($records as $record) {
                echo $this->encode($record, $pretty) . PHP_EOL;
            }
        }

        /**
         * @param iterable<array<string, mixed>> $records
         */
        public function writeToFile(string $filePath, iterable $records, bool $pretty = false): void
        {
            $handle = fopen($filePath, 'wb');

            if ($handle === false) {
                throw new \RuntimeException("Unable to open output file: {$filePath}");
            }

            try {
                foreach ($records as $record) {
                    fwrite($handle, $this->encode($record, $pretty) . PHP_EOL);
                }
            } finally {
                fclose($handle);
            }
        }

        /**
         * @param array<string, mixed> $record
         */
        private function encode(array $record, bool $pretty = false): string
        {
            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

            if ($pretty) {
                $flags |= JSON_PRETTY_PRINT;
            }

            return json_encode($record, $flags) ?: '{}';
        }
    }