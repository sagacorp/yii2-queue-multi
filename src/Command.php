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
    public function actionListen(?string $queue = null): int
    {
        $queueInstance = $this->queue->getQueue($queue);

        if ($this->isolate && $queueInstance instanceof CliQueue && $this->queue->messageHandler) {
            $queueInstance->messageHandler = $this->queue->messageHandler;
        }

        if (method_exists($queueInstance, 'run')) {
            return call_user_func_array($queueInstance->run, [true]);
        }

        if (method_exists($queueInstance, 'listen')) {
            return call_user_func($queueInstance->listen);
        }

        return ExitCode::CONFIG;
    }

    protected function isWorkerAction($actionID): bool
    {
        return 'listen' === $actionID;
    }
}
