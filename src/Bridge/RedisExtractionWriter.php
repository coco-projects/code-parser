<?php

    declare(strict_types=1);

    namespace Coco\codeParser\Bridge;

    use Coco\codeParser\Config\RedisExtractionPayload;
    use Redis;

    final class RedisExtractionWriter
    {
        private Redis $redis;

        public function __construct(
            private readonly RedisExtractionPayload $payload,
        ) {
            $this->redis = new Redis();
            $this->redis->connect($payload->redisHost, $payload->redisPort, 10.0);

            if ($payload->redisPassword !== '') {
                $this->redis->auth($payload->redisPassword);
            }

            $this->redis->select($payload->redisDbIndex);
        }

        public function startTask(int $totalFiles): void
        {
            $metaKey = $this->payload->metaKey();

            $this->redis->del(
                $metaKey,
                $this->payload->filesKey(),
                $this->payload->recordsKey(),
                $this->payload->errorsKey()
            );

            $this->redis->hMSet($metaKey, [
                'task_id' => $this->payload->taskId,
                'project_name' => $this->payload->projectName,
                'project_root' => $this->payload->projectRoot,
                'status' => 'running',
                'total_files' => (string) $totalFiles,
                'processed_files' => '0',
                'success_files' => '0',
                'skipped_files' => '0',
                'failed_files' => '0',
                'started_at' => (string) time(),
                'finished_at' => '',
                'output_path' => $this->payload->outputPath,
                'message' => '',
            ]);

            $this->expireAll();
        }

        public function appendFileRecord(string $relativePath, array $record): void
        {
            $this->redis->rPush($this->payload->filesKey(), $relativePath);
            $this->redis->rPush(
                $this->payload->recordsKey(),
                json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
            );
            $this->redis->hIncrBy($this->payload->metaKey(), 'processed_files', 1);
            $this->expireAll();
        }

        public function markSuccess(): void
        {
            $this->redis->hIncrBy($this->payload->metaKey(), 'success_files', 1);
            $this->expireAll();
        }

        public function markSkipped(): void
        {
            $this->redis->hIncrBy($this->payload->metaKey(), 'skipped_files', 1);
            $this->expireAll();
        }

        public function markFailed(string $relativePath, string $errorMessage): void
        {
            $this->redis->hIncrBy($this->payload->metaKey(), 'failed_files', 1);
            $this->redis->rPush(
                $this->payload->errorsKey(),
                json_encode([
                    'relative_path' => $relativePath,
                    'error' => $errorMessage,
                    'stage' => 'extract',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'
            );
            $this->expireAll();
        }

        public function complete(string $message = 'completed'): void
        {
            $this->redis->hMSet($this->payload->metaKey(), [
                'status' => 'completed',
                'finished_at' => (string) time(),
                'message' => $message,
            ]);
            $this->expireAll();
        }

        public function fail(string $message): void
        {
            $this->redis->hMSet($this->payload->metaKey(), [
                'status' => 'failed',
                'finished_at' => (string) time(),
                'message' => $message,
            ]);
            $this->expireAll();
        }

        private function expireAll(): void
        {
            $ttl = $this->payload->redisTtlSeconds;
            if ($ttl <= 0) {
                return;
            }

            $this->redis->expire($this->payload->metaKey(), $ttl);
            $this->redis->expire($this->payload->filesKey(), $ttl);
            $this->redis->expire($this->payload->recordsKey(), $ttl);
            $this->redis->expire($this->payload->errorsKey(), $ttl);
        }
    }