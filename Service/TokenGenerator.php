<?php
namespace Sh4bang\UserBundle\Service;

/**
 * Class TokenGenerator
 *
 * @package Sh4bang\UserBundle\Service
 * @link https://stackoverflow.com/questions/3290283/what-is-a-good-way-to-produce-a-random-site-salt-to-be-used-in-creating-passwo/3291689#3291689
 */
class TokenGenerator
{
    /**
     * Create a secure randomization
     *
     * @param $min
     * @param $max
     * @return int
     */
    private function cryptoRandSecure($min, $max)
    {
        $range = $max - $min;
        if ($range < 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    /**
     * Generate a random token
     *
     * @param int $length
     * @return string
     */
    public function getToken($length=32)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        for ($i=0; $i<$length; $i++) {
            $token .= $codeAlphabet[$this->cryptoRandSecure(0,strlen($codeAlphabet))];
        }
        return $token;
    }
}
