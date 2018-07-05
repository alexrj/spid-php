<?php
require_once('../setup/Setup.php');
//require_once('vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use setup\Setup;

class SetupTest extends TestCase
{

    public function testLoadConfig(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Setup::loadConfig();
     }
	
}
