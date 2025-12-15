# Multi queue driver for Yii2 Queue

This extension is a [Yii2 Queue](https://github.com/yiisoft/yii2-queue) driver for multi queue component.

## Installation

Install this extension with [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist sagacorp/yii2-queue-multi
```

or add the extension to your composer json.

```
"sagacorp/yii2-queue-multi": "~1.0.0"
```

## Basic Usage

```php
return [
    'components' => [
        'queue' => [
            'class' => \yii\queue\multi\Queue::class,
            'queues' => [
                \yii\queue\multi\Queue::DEFAULT_QUEUE => [
                    'class' => \yii\queue\redis\Queue::class,
                    'redis' => 'redis'
                ],
            ],
        ],
    ],
];
 ```

The `default` queue is **required**, the you can add as many queues as you want.

When pushing a *Job* to the multi queue, the queue look if the *Job* implement the `QueueAwareJobInterface` interface.

Then it push the *Job* in the right queue.

## Isolate mode

In case of isolation mode, the jobs are executed on the multi queue directly.