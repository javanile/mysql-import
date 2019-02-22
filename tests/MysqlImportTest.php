<?php

namespace Javanile\IpQueue\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class MysqlImportTest extends TestCase
{
    public function testImportByDefault()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'root'], [$sqlFile]);
        $this->assertEquals($message, $app->run());

        $app = new MysqlImport([], ['-proot', $sqlFile]);
        $this->assertEquals($message, $app->run());
    }

    public function testImportWithUserAndPassword()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'root'], [$sqlFile]);
        $this->assertEquals($message, $app->run());

        $app = new MysqlImport([], ['-uroot', '-proot', $sqlFile]);
        $this->assertEquals($message, $app->run());
    }

    public function testDropAndCreateDatabase()
    {
        $sqlFile = __DIR__.'/fixtures/database.sql';
        $message = "[mysql-import] database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'root'], [$sqlFile]);

        $app->run();
        $app->drop('yes');

        $this->assertEquals($message, $app->run());
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
                'MYSQL_HOST'          => 'wrong',
                'MYSQL_ROOT_PASSWORD' => 'wrong',
            ],
            [__DIR__.'/fixtures/database.sql']
        );

        $this->assertStringStartsWith(
            "[mysql-import] connection problem for user 'root' on host 'wrong' with error: ",
            $app->run()
        );
    }

    public function testMissingSqlFile()
    {
        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'root'], []);
        $this->assertEquals('[mysql-import] required sql file to import.', $app->run());

        $sqlFile = __DIR__.'/fixtures/not_exists.sql';
        $app = new MysqlImport([], ['-proot', $sqlFile]);
        $this->assertEquals("[mysql-import] sql file '{$sqlFile}' not found.", $app->run());
    }
}
