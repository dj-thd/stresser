<?php

namespace DjThd;

class RequestBuilder
{
	protected $modelRequestHeaders;
	protected $modelRequestBody;

	protected $enableIp = false;
	protected $enableString = false;
	protected $enableInt = false;

	public function __construct($modelRequest, $newHost = false)
	{
		$this->modelRequestHeaders = $this->parseHeaders($modelRequest);
		$this->modelRequestBody = $this->parseBody($modelRequest);

		if($newHost !== false) {
			$this->modelRequestHeaders = preg_replace('/Host: [^\r\n]*/i', 'Host: '.$newHost, $this->modelRequestHeaders);
		}

		if(strpos($modelRequest, '__IP__') !== false) {
			$this->enableIp = true;
		}
		if(strpos($modelRequest, '__STRING__') !== false) {
			$this->enableString = true;
		}
		if(strpos($modelRequest, '__INT__') !== false) {
			$this->enableInt = true;
		}
	}

	public function buildRequest()
	{
		$headers = $this->modelRequestHeaders;
		$body = $this->modelRequestBody;
		if($this->enableIp) {
			$random = mt_rand(0,255).'.'.mt_rand(0,255).'.'.mt_rand(0,255).'.'.mt_rand(0,255);
			$headers = str_replace('__IP__', $random, $headers);
			$body = str_replace('__IP__', $random, $body);
		}
		if($this->enableString) {
			$headers = str_replace('__STRING__', Util::randomString(4, mt_rand(4,10)), $headers);
			$body = str_replace('__STRING__', Util::randomString(4, mt_rand(4,10)), $body);
		}
		if($this->enableInt) {
			$headers = str_replace('__INT__', mt_rand(0,9999999), $headers);
			$body = str_replace('__INT__', mt_rand(0,9999999), $body);
		}
		$headers = preg_replace('/Content-Length: \\d+/i', 'Content-Length: '.strlen($body), $headers);
		return "$headers\r\n$body";
	}

	public function parseHeaders($request)
	{
		$result = '';
		$exp = explode("\n", $request);
		foreach($exp as $line) {
			$line = rtrim($line, "\r\n");
			if($line === '') {
				break;
			} else {
				$result .= "$line\r\n";
			}
		}
		return $result;
	}

	public function parseBody($request)
	{
		$result = '';
		$exp = explode("\n", $request);
		$addBody = false;
		foreach($exp as $line) {
			if($addBody) {
				$result .= "$line\n";
			} else if(rtrim($line, "\r\n") === '') {
				$addBody = true;
			}
		}
		return rtrim($result, "\r\n");
	}
}
