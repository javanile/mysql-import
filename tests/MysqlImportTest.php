<?php

namespace Javanile\IpQueue\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class MysqlImportTest extends TestCase
{
    public function testImportByDefault()
    {
        $app = new MysqlImport(
            [ 'MYSQL_ROOT_PASSWORD' => 'root' ],
            [ __DIR__.'/fixtures/database.sql' ]
        );

        $this->assertEquals($app->run(), "");
    }
}
