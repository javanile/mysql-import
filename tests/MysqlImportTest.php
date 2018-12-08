<?php

namespace Javanile\IpQueue\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class MysqlImportTest extends TestCase
{
    public function testImportByDefault()
    {
        $app = new MysqlImport(
            ['MYSQL_ROOT_PASSWORD' => 'root'],
            [__DIR__.'/fixtures/database.sql']
        );

        $this->assertEquals("[mysql-import] database named 'database' successfully imported.", $app->run());
    }

    public function testConnectionProblemWrongPassword()
    {
        $app = new MysqlImport(
            ['MYSQL_ROOT_PASSWORD' => 'wrong'],
            [__DIR__.'/fixtures/database.sql']
        );

        $this->assertStringStartsWith(
            "[mysql-import] connection problem for user 'root' on host 'mysql' with error: ",
            $app->run()
        );
    }

    public function testConnectionProblemWrongHost()
    {
        $app = new MysqlImport(
            [
                'MYSQL_HOST' => 'wrong',
                'MYSQL_ROOT_PASSWORD' => 'wrong',
            ],
            [__DIR__.'/fixtures/database.sql']
        );

        $this->assertStringStartsWith(
            "[mysql-import] connection problem for user 'root' on host 'wrong' with error: ",
            $app->run()
        );
    }
}
