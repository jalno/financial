<?php
namespace packages\financial\listeners;

use packages\cronjob\{task\Schedule, Task, events\Tasks};
use packages\financial\processes;

class Cronjob {
	public function tasks(tasks $event): void {
		$event->addTask($this->reminder());
		$event->addTask($this->autoExpire());
	}
	private function reminder(){
		$task = new task();
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
	private function autoExpire(): Task {
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
