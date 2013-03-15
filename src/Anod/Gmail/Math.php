<?php
namespace Anod\Gmail;
/**
 * 
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 *
 */
class Math {
	
	public static function bchexdec($hex)
	{
		$dec = 0;
		$len = strlen($hex);
		for ($i = 1; $i <= $len; $i++) {
			$dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
		}
		return $dec;
	}
	
	public static function bcdechex($number)
	{
		$hexval = '';
		while($number != '0')
		{
			$hexval = dechex(bcmod($number,'16')).$hexval;
			$number = bcdiv($number,'16',0);
		}
		return $hexval;
	}
	
}