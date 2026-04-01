<?php

namespace yii\queue\multi;

use sagacorp\queue\contracts\BatchableQueueInterface;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\queue\cli\Queue as CliQueue;
use yii\queue\Queue as BaseQueue;

class Queue extends CliQueue
{
    public const string DEFAULT_QUEUE = 'default';

    public $commandClass = Command::class;

    /** @var BaseQueue[] */
    public array $queues = [];

    protected string $currentQueue;

    /** @var array<int, array{message: string, ttr: int, delay: int, priority: mixed}> */
    private array $batchBuffer = [];

    private bool $batching = false;

    public function getQueue(string $queue): BaseQueue
    {
        if (!isset($this->queues[$queue])) {
            $queue = self::DEFAULT_QUEUE;
        }

        $this->queues[$queue] = Instance::ensure($this->queues[$queue], BaseQueue::class);

        return $this->queues[$queue];
    }

    #[\Override]
    public function init(): void
    {
        parent::init();

        if (!isset($this->queues[self::DEFAULT_QUEUE])) {
            throw new InvalidConfigException("'" . self::DEFAULT_QUEUE . "' queue must be set.");
        }

        $this->currentQueue = self::DEFAULT_QUEUE;
    }

    #[\Override]
    public function push($job): ?string
    {
        $this->currentQueue = $job instanceof QueueAwareJobInterface
            ? ($job->getQueue() ?? self::DEFAULT_QUEUE)
            : self::DEFAULT_QUEUE;

        try {
            $result = parent::push($job);
        } finally {
            $this->currentQueue = self::DEFAULT_QUEUE;
        }

        return $result;
    }

    /**
     * @param array<int, mixed> $jobs
     *
     * @return string[]
     */
    public function pushBatch(array $jobs, string $queue = self::DEFAULT_QUEUE): array
    {
        $backend = $this->getQueue($queue);

        if (!$backend instanceof BatchableQueueInterface) {
            return array_map(fn ($job) => $this->push($job), $jobs);
        }

        $this->currentQueue = $queue;
        $this->batching = true;
        $this->batchBuffer = [];

        try {
            foreach ($jobs as $job) {
                parent::push($job);
            }

            return $backend->pushBatch($this->batchBuffer);
        } finally {
            $this->batching = false;
            $this->batchBuffer = [];
            $this->currentQueue = self::DEFAULT_QUEUE;
        }
    }

    public function status($id): bool
    {
        return $this->getQueue($this->currentQueue)->status($id);
    }

    public function trigger($name, $event = null): void
    {
        if ($this->batching && in_array($name, [self::EVENT_BEFORE_PUSH, self::EVENT_AFTER_PUSH], true)) {
            return;
        }

        parent::trigger($name, $event);
    }

    protected function pushMessage($message, $ttr, $delay, $priority): string
    {
        if ($this->batching) {
            $this->batchBuffer[] = [
                'message' => $message,
                'ttr' => $ttr,
                'delay' => $delay,
                'priority' => $priority,
            ];

            return '';
        }

        return $this->getQueue($this->currentQueue)->pushMessage($message, $ttr, $delay, $priority);
    }
}
