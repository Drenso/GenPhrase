<?php
namespace Drenso\GenPhrase\Tests\Mock;

/**
 * MockRandomBytes.
 * 
 * Only used in testing.
 * 
 * @return string
 * @author timoh <timoh6@gmail.com>
 */
class MockRandomBytes
{
    public function getRandomBytes($count)
    {
        static $number = 0;

        $ret = $number;
        $number++;
        
        return pack("N*", $ret);
    }
}
