#!/usr/bin/env php
<?php

require dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$loop = React\EventLoop\Factory::create();

$request_timeout = 30;
$request_concurrency = 500;
$request_per_socket = 200;
$quiet_mode = in_array('--quiet', $argv) ? true : false;
$socks_proxy = in_array('--socks5',$argv) ? $argv[array_search('--socks5', $argv) + 1] : null;

$url = parse_url(isset($argv[1]) ? $argv[1] : '');

if(!isset($url['host']) || in_array('-h',$argv)) {
	fputs(STDERR, "Usage: {$argv[0]} (URL) (request/template/file) [options]\n\n");
	die();
}

$ip = gethostbyname($url['host']);
$tls = isset($url['scheme']) ? $url['scheme'] === 'https' : false;
$port = isset($url['port']) ? $url['port'] : ($tls ? 443 : 80);

$connector = new React\Socket\Connector($loop, array(
	'tcp' => true,
	'tls' => array(
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true,
		'disable_compression' => false
	),
	'dns' => false,
	'timeout' => $request_timeout,
	'unix' => false
));

if(!isset($argv[2]) || !file_exists($argv[2])) {
	fputs(STDERR, "Usage: {$argv[0]} (URL) (request/template/file)\n\n");
	die();
}

if(isset($socks_proxy)){
	$connector = new Clue\React\Socks\Client($socks_proxy, $connector);
}

$builder = new DjThd\RequestBuilder(file_get_contents($argv[2]), $ip, $url['host'] !== $ip);
$sender = new DjThd\RequestSender($loop, $builder, $connector, $ip, $port, $tls, $request_concurrency, $request_per_socket, $quiet_mode);
$sender->run();

$loop->run();
