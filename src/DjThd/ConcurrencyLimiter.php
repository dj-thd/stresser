<?php

namespace DjThd;

use \Evenement\EventEmitter;

class ConcurrencyLimiter extends EventEmitter
{
	protected $loop;
	protected $maxJobs;
	protected $callback = null;

	protected $isInputPaused = false;
	protected $currentJobs = 0;
	protected $queue = array();

	protected function pauseInput()
	{
		if(!$this->isInputPaused) {
			$this->emit('pause');
		}
		$this->isInputPaused = true;
	}

	protected function resumeOutput()
	{
		if($this->isInputPaused) {
			$this->emit('resume');
		}
		$this->isInputPaused = false;
	}

	public function __construct($loop, $maxJobs)
	{
		$this->loop = $loop;
		$this->maxJobs = $maxJobs;
		$this->pauseInput();
	}

	public function run($callback)
	{
		$this->callback = $callback;
		$this->queue = array();
		$this->concurrentJobs = 0;
		$this->resumeOutput();
	}

	public function handleData($data)
	{
		if(!$this->callback) {
			$this->pauseInput();
			return;
		}

		if($this->concurrentJobs > $this->maxJobs) {
			$this->pauseInput();
			$this->enqueueItem($data);
		} else {
			$this->concurrentJobs++;
			call_user_func($this->callback, $data, array($this, 'finishedJob'));
		}
	}

	public function finishedJob()
	{
		$this->concurrentJobs--;
		$this->loop->futureTick(array($this, 'processPending'));
	}

	public function processPending()
	{
		if(!empty($this->queue)) {
			$item = array_pop($this->queue);
			$this->concurrentJobs++;
			call_user_func($this->callback, $item, array($this, 'finishedJob'));
		} else {
			$this->resumeOutput();
		}
	}

	public function enqueueItem($item)
	{
		$this->queue[] = $item;
	}
}
