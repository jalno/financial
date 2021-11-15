<?php
namespace packages\financial\listeners;

use packages\base\Exception;
use packages\cronjob\{task\Schedule, Task, events\Tasks};
use packages\financial\processes;

class Cronjob {

	/**
	 * @param Tasks $event
	 */
	public function tasks($event): void {
		if (!class_exists(Tasks::class)) {
			throw new Exception("Cronjob package is not installed");
		}

		$event->addTask($this->reminder());
		$event->addTask($this->autoExpire());
	}

	/**
	 * @return Task
	 */
	private function reminder(){
		if (!class_exists(Task::class) or !class_exists(Schedule::class)) {
			throw new Exception("Cronjob package is not installed");
		}
		$task = new Task();
		$task->name = "financial_task_reminder";
		$task->process = processes\transactions::class."@reminder";
		$task->parameters = [];
		$task->schedules = [
			new schedule([
				'minute' => 0
			]),
			new schedule([
				'hour' => 0
			])
		];
		return $task;
	}

	/**
	 * @return Task
	 */
	private function autoExpire() {
		if (!class_exists(Task::class) or !class_exists(Schedule::class)) {
			throw new Exception("Cronjob package is not installed");
		}
		$task = new Task();
		$task->name = "financial_task_autoexpire";
		$task->process = processes\Transactions::class . "@autoExpire";
		$task->parameters = [];
		$task->schedules = [
			new Schedule([
				'minute' => 0,
			]),
		];
		return $task;
	}
}
