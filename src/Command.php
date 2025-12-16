<?php

namespace yii\queue\multi;

use yii\console\ExitCode;
use yii\queue\cli\Command as CliCommand;
use yii\queue\cli\Queue as CliQueue;

/**
 * @property Queue $queue
 */
class Command extends CliCommand
{
    public function actionListen(string $queue = Queue::DEFAULT_QUEUE, int $timeout = 10): int
    {
        $queueInstance = $this->getQueue($queue);

        if (method_exists($queueInstance, 'listen')) {
            $queueInstance->listen();

            return ExitCode::OK;
        }

        if (method_exists($queueInstance, 'run')) {
            return call_user_func_array([$queueInstance, 'run'], [true, $timeout]) ?? ExitCode::OK;
        }

        return ExitCode::CONFIG;
    }

    public function actionRun(string $queue = Queue::DEFAULT_QUEUE): int
    {
        $queueInstance = $this->getQueue($queue);

        if (method_exists($queueInstance, 'run')) {
            return call_user_func_array([$queueInstance, 'run'], [false]);
        }

        return ExitCode::CONFIG;
    }

    protected function getQueue(string $queue): \yii\queue\Queue
    {
        $queueInstance = $this->queue->getQueue($queue);

        if ($this->isolate && $queueInstance instanceof CliQueue && $this->queue->messageHandler) {
            $queueInstance->messageHandler = $this->queue->messageHandler;
        }

        return $queueInstance;
    }

    protected function isWorkerAction($actionID): bool
    {
        return in_array($actionID, ['run', 'listen'], true);
    }
}
