<?php
/**
 * @author Alex Gavrishev <alex.gavrishev@gmail.com>
 */
namespace Anod\Gmail;

/**
 * Helper to convert between different representations of gmail message_id *
 */
class Math
{
    /**
     * Converts hex number into large integer
     * \Anod\Gmail\Math::bchexdec("13d6daab0816ace7") == "1429570359846677735"
     * @param string $hex
     * @return number
     */
    public static function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }
    
    /**
     * Converts large number into hex representation
     * \Anod\Gmail\Math::bchexdec("1429570359846677735") == "13d6daab0816ace7"
     * @param string $number
     * @return string
     */
    public static function bcdechex($number)
    {
        $hexval = '';
        while ($number != '0') {
            $hexval = dechex(bcmod($number, '16')).$hexval;
            $number = bcdiv($number, '16', 0);
        }
        return $hexval;
    }
}
