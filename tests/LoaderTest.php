<?php

namespace Javanile\MysqlImport\Tests;

use Javanile\MysqlImport\Loader;
use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    public function testLoader()
    {
        $loader = new Loader();
        $startTime = microtime(true);
        $loader->waiting(10);
        $delay = microtime(true) - $startTime;
        $this->assertGreaterThan(10, $delay);
        $this->assertLessThan(11, $delay);
    }
}
