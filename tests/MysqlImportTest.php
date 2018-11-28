<?php

namespace Javanile\IpQueue\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class MysqlImportTest extends TestCase
{
    public function testGetApi()
    {
        $app = new MysqlImport(
            [ 'REQUEST_METHOD' => 'GET' ],
            [ 'Host' => 'test.ipqueue.com' ]
        );

        $this->assertEquals($app->run(), "");
    }
}
