<?php

namespace DjThd;

class Util
{
	public static function randomString($min, $max = false)
	{
		if($max === false) {
			$max = $min;
		}
		$num = mt_rand($min, $max);
		return substr(str_replace(array('/', '+'), array(chr(0x30+mt_rand(0,9)), chr(0x30+mt_rand(0,9))), base64_encode(random_bytes($num))),0,$num);
	}
}
