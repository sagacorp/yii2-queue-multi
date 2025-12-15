<?php

namespace yii\queue\multi;

use yii\queue\JobInterface;

interface QueueAwareJobInterface extends JobInterface
{
    public function getQueue(): ?string;
}
