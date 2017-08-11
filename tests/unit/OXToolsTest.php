<?php

define('TESTS_UNIT_PATH', dirname(__FILE__));
define('ADVMAN_PATH', TESTS_UNIT_PATH . '/../..');
define('ADVMAN_LIB', ADVMAN_PATH . '/lib/Advman');
define('OX_LIB', ADVMAN_PATH . '/lib/OX');
require_once(OX_LIB . '/Tools.php');

class OXToolsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    // tests
    public function testExplodeFormat()
    {
        list($a,$b) = OX_Tools::explode_format('728x90');
        $this->assertEquals($a, 728);
        $this->assertEquals($b, 90);
    }

    public function testGetIntMax()
    {
        $i = OX_Tools::get_int_max();
        $this->assertEquals($i, 9223372036854775807);
    }

}