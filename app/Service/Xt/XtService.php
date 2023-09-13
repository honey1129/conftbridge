<?php

namespace App\Service\Xt;

class XtService
{
    public static function encode(string $string)
    {
        $string = '10e5' . $string;
        return Base58Check::encode($string, 0, false);
    }

    public static function decode(string $string)
    {
        return Base58Check::decode($string, 0, 3);
    }

    public static function toXtAddress(string $string)
    {
        if (mb_substr($string, 0, 2) === '0x') {
            $string = mb_substr($string, 2);
        }
        return self::encode($string);
    }
    public static function addressHexString(string $string)
    {
        $str = self::decode($string);
        if (mb_strlen($str) == 44 && mb_substr($str, 0, 4) === '10e5') {
            return '0x' . mb_substr($str, 4);
        } else {
            throw new \Exception('xtAddress err');
        }
    }
}