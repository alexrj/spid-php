<?php
require_once('../setup/Setup.php');
require_once('vendor/autoload.php');

use PHPUnit\Framework\TestCase;

class SetupTest extends TestCase
{
    public function testExpectFooActualFoo()
    {
        $this->expectOutputString('foo');
        print 'foo';
    }
	
}
