<?php

namespace Javanile\MysqlImport\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class MysqlImportTest extends TestCase
{
    public function testWrongArguments()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Unknown option '-kWrong'.";

        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file, '-kWrong']);
        $this->assertEquals($message, $mysqlImport->run());
        $this->assertEquals(2, $mysqlImport->getExitCode());

        $mysqlImport = new MysqlImport([], ['-psecret', '-kWrong', $file]);
        $this->assertEquals($message, $mysqlImport->run());
        $this->assertEquals(2, $mysqlImport->getExitCode());
    }

    public function testImportByDefault()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Database named 'database' successfully imported.";

        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file]);
        $this->assertEquals($message, $mysqlImport->run());
        $mysqlImport->drop('yes');

        $mysqlImport = new MysqlImport([], ['-psecret', $file]);
        $this->assertEquals($message, $mysqlImport->run());
        $mysqlImport->drop('yes');
    }

    public function testImportWithUserAndPassword()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Database named 'database' successfully imported.";

        $mysqlImport = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'secret'], [$file]);
        $this->assertEquals($message, $mysqlImport->run());
        $mysqlImport->drop('yes');

        $mysqlImport = new MysqlImport([], ['-uroot', '-psecret', $file]);
        $this->assertEquals($message, $mysqlImport->run());
        $mysqlImport->drop('yes');
    }

    public function testDropAndCreateDatabase()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Database named 'database' successfully imported.";

        $mysqlImport = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'secret'], [$file]);

        $mysqlImport->run();
        $mysqlImport->drop();
        $mysqlImport->drop('yes');

        $this->assertEquals($message, $mysqlImport->run());
        $mysqlImport->drop('yes');
    }

    public function testNotBlankDatabase()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = 'Required blank database for import.';

        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file]);
        $mysqlImport->run();

        $mysqlImport = new MysqlImport([], ['-psecret', $file]);
        $this->assertEquals($message, $mysqlImport->run());

        $mysqlImport = new MysqlImport(['DB_USER' => 'root', 'DB_PASSWORD' => 'secret'], [$file]);
        $this->assertEquals($message, $mysqlImport->run());
        $mysqlImport->drop('yes');
    }

    public function testForceNotBlankDatabase()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Duplicate entry 'A007' for key 'PRIMARY'";

        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file]);
        $mysqlImport->run();

        $mysqlImport = new MysqlImport([], ['-psecret', $file, '--force']);
        $this->assertEquals($message, $mysqlImport->run());

        $mysqlImport = new MysqlImport(['DB_USER' => 'root', 'DB_PASSWORD' => 'secret'], [$file, '--force']);
        $this->assertEquals($message, $mysqlImport->run());
        $mysqlImport->drop('yes');
    }

    public function testConnectionProblemWrongPassword()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Connection problem for 'root' on 'mysql' with error: ";

        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'wrong'], [$file]);
        $this->assertStringStartsWith($message, $mysqlImport->run());
    }

    public function testConnectionProblemWrongHost()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Connection problem for 'root' on 'wrong' with error: ";

        $mysqlImport = new MysqlImport(['MYSQL_HOST' => 'wrong', 'MYSQL_ROOT_PASSWORD' => 'wrong'], [$file]);
        $this->assertStringStartsWith($message, $mysqlImport->run());

        $mysqlImport = new MysqlImport(
            ['MYSQL_HOST' => 'wrong', 'MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'wrong'],
            [$file]
        );
        $this->assertStringStartsWith($message, $mysqlImport->run());
        $this->assertEquals(1, $mysqlImport->getExitCode());
    }

    public function testMissingSqlFile()
    {
        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], []);
        $this->assertEquals('Required sql file to import.', $mysqlImport->run());
    }

    public function testNotExistsSqlFile()
    {
        $file = __DIR__.'/fixtures/not_exists.sql';
        $mysqlImport = new MysqlImport([], ['-psecret', $file]);
        $this->assertEquals("Sql file '{$file}' not found.", $mysqlImport->run());
    }

    public function testMissingRootPassword()
    {
        $mysqlImport = new MysqlImport([], [__DIR__.'/fixtures/database.sql']);
        $this->assertEquals('Required at least root password.', $mysqlImport->run());
    }

    public function testHostPortFix()
    {
        $mysqlImport = new MysqlImport(['WORDPRESS_DB_HOST' => 'db:10101'], [__DIR__.'/fixtures/database.sql']);
        $this->assertEquals(['host' => 'db', 'port' => 10101, 'database' => 'database'], $mysqlImport->getInfo());
    }

    public function testDefaultDatabaseName()
    {
        $mysqlImport = new MysqlImport(['WORDPRESS_DB_PASSWORD' => 'secret'], [__DIR__.'/fixtures/database.sql']);
        $this->assertEquals(['host' => 'mysql', 'port' => 3306, 'database' => 'wordpress'], $mysqlImport->getInfo());
    }

    public function testSyntaxError()
    {
        $file = __DIR__.'/fixtures/syntax_error.sql';
        $message = 'You have an error in your SQL syntax; check the manual that corresponds to';

        $mysqlImport = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file]);
        $this->assertStringStartsWith($message, $mysqlImport->run());
    }
}
