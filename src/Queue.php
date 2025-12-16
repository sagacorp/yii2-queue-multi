<?php

namespace yii\queue\multi;

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

    public function getQueue(string $queue): BaseQueue
    {
        if (! isset($this->queues[$queue])) {
            $queue = self::DEFAULT_QUEUE;
        }

        $this->queues[$queue] = Instance::ensure($this->queues[$queue], BaseQueue::class);

        return $this->queues[$queue];
    }

    #[\Override]
    public function init(): void
    {
        parent::init();

        if (! isset($this->queues[self::DEFAULT_QUEUE])) {
            throw new InvalidConfigException("'".self::DEFAULT_QUEUE."' queue must be set.");
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

    public function status($id): bool
    {
        return $this->getQueue($this->currentQueue)->status($id);
    }

    protected function pushMessage($message, $ttr, $delay, $priority): string
    {
        return $this->getQueue($this->currentQueue)->pushMessage($message, $ttr, $delay, $priority);
    }
}
