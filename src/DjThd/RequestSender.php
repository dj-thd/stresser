<?php

namespace DjThd;

class RequestSender
{
	protected $requestBuilder = null;
	protected $connector;
	protected $ip = '';
	protected $port = 80;
	protected $tls = false;
	protected $maxConcurrency = 1;
	protected $concurrency = 0;

	public function __construct($requestBuilder, $connector, $ip, $port, $tls, $maxConcurrency)
	{
		$this->requestBuilder = $requestBuilder;
		$this->connector = $connector;
		$this->ip = $ip;
		$this->port = $port;
		$this->tls = $tls;
		$this->maxConcurrency = $maxConcurrency;
	}

	public function run()
	{
		while($this->concurrency < $this->maxConcurrency) {
			$this->concurrency++;
			$this->connector->connect(($this->tls ? 'tls' : 'tcp') . '://' . $this->ip . ':' . $this->port)->then(function($connection) {
				$connection->on('end', function() {
					$connection->close();
				});
				$connection->on('close', function() {
					$this->concurrency--;
					$this->run();
				});
				$connection->on('error', function() {
					$this->concurrency--;
					$this->run();
				});
				$this->writeChunk($connection, 50);
			});
		}
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
