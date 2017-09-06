<?php
namespace packages\financial\listeners;
use \packages\cronjob\events\tasks;
use \packages\cronjob\task;
use \packages\cronjob\task\schedule;
use packages\financial\processes;
class cronjob{
	public function tasks(tasks $event){
		$event->addTask($this->reminder());
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
}
