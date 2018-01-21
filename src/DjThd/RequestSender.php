<?php

namespace DjThd;

class RequestSender
{
	protected $requestBuilder = null;
	protected $connector;
	protected $ip = '';
	protected $port = 80;
	protected $tls = false;
	protected $maxConcurrency = 10;
	protected $reqPerSocket = 50;
	protected $concurrency = 0;
	protected $running = false;

	public function __construct($requestBuilder, $connector, $ip, $port, $tls, $maxConcurrency, $reqPerSocket)
	{
		$this->requestBuilder = $requestBuilder;
		$this->connector = $connector;
		$this->ip = $ip;
		$this->port = $port;
		$this->tls = $tls;
		$this->maxConcurrency = $maxConcurrency;
		$this->reqPerSocket = $reqPerSocket;
	}

	public function run()
	{
		if($this->running) {
			return;
		}
		$this->running = true;
		while($this->concurrency < $this->maxConcurrency) {
			$this->concurrency++;
			echo '.';
			$this->connector->connect(($this->tls ? 'tls' : 'tcp') . '://' . $this->ip . ':' . $this->port)->then(function($connection) {
				$connection->on('end', function() use ($connection) {
					echo 'n';
					$connection->close();
				});
				$connection->on('close', function() {
					if($this->concurrency > 0) {
						echo '!';
						$this->concurrency--;
					}
				});
				$connection->on('error', function() use ($connection) {
					echo 'e';
					$connection->close();
				});
				$this->writeChunk($connection, $this->reqPerSocket);
			})->otherwise(function($e) {
				if($this->concurrency > 0) {
					echo 'E';
					$this->concurrency--;
				}
			});
		}
		$this->running = false;
	}

	public function writeChunk($stream, $count)
	{
		$chunk = '';
		for($i = 0; $i < $count; $i++) {
			$chunk .= $this->requestBuilder->buildRequest();
		}
		$stream->end($chunk);
	}
}
