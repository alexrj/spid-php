<?php
require_once('../setup/Setup.php');
//require_once('vendor/autoload.php');

use PHPUnit\Framework\TestCase;
use setup\Setup;
use Composer\Script\Event;

class SetupTest extends TestCase
{

    public function testSetup(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $event = "post-update-cmd": [
            "setup\\Setup::setup"
        ];

        Setup::setup($event);
     }
	
}
