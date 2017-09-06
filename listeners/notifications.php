<?php
namespace packages\financial\listeners;
use \packages\notifications\events;
use \packages\financial\events as financialEevents;
class notifications{
	public function events(events $events){
		$events->add(financialEevents\transactions\add::class);
		$events->add(financialEevents\transactions\edit::class);
		$events->add(financialEevents\transactions\expire::class);
		$events->add(financialEevents\transactions\pay::class);
		$events->add(financialEevents\transactions\reminder::class);
	}
}