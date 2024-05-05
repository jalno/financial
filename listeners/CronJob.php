<?php

namespace packages\financial\Listeners;

use packages\base\Exception;
use packages\cronjob\Events\Tasks;
use packages\cronjob\Task;
use packages\cronjob\Task\Schedule;
use packages\financial\Processes;

class CronJob
{
    /**
     * @param Tasks $event
     */
    public function tasks($event): void
    {
        if (!class_exists(Tasks::class)) {
            throw new Exception('Cronjob package is not installed');
        }

        $event->addTask($this->reminder());
        $event->addTask($this->autoExpire());
    }

    /**
     * @return Task
     */
    private function reminder()
    {
        if (!class_exists(Task::class) or !class_exists(Schedule::class)) {
            throw new Exception('Cronjob package is not installed');
        }
        $task = new Task();
        $task->name = 'financial_task_reminder';
        $task->process = Processes\Transactions::class.'@reminder';
        $task->parameters = [];
        $task->schedules = [
            new Schedule([
                'minute' => 0,
            ]),
            new Schedule([
                'hour' => 0,
            ]),
        ];

        return $task;
    }

    /**
     * @return Task
     */
    private function autoExpire()
    {
        if (!class_exists(Task::class) or !class_exists(Schedule::class)) {
            throw new Exception('Cronjob package is not installed');
        }
        $task = new Task();
        $task->name = 'financial_task_autoexpire';
        $task->process = Processes\Transactions::class.'@autoExpire';
        $task->parameters = [];
        $task->schedules = [
            new Schedule([
                'minute' => 0,
            ]),
        ];

        return $task;
    }
}
