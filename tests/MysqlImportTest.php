<?php

namespace Javanile\MysqlImport\Tests;

use Javanile\MysqlImport\MysqlImport;
use PHPUnit\Framework\TestCase;

class MysqlImportTest extends TestCase
{
    public function testImportByDefault()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');

        $app = new MysqlImport([], ['-psecret', $file]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');
    }

    public function testImportWithUserAndPassword()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'secret'], [$file]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');

        $app = new MysqlImport([], ['-uroot', '-psecret', $file]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');
    }

    public function testDropAndCreateDatabase()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Database named 'database' successfully imported.";

        $app = new MysqlImport(['MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'secret'], [$file]);

        $app->run();
        $app->drop();
        $app->drop('yes');

        $this->assertEquals($message, $app->run());
        $app->drop('yes');
    }

    public function testNotBlankDatabase()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = 'Required blank database for import.';

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file]);
        $app->run();

        $app = new MysqlImport([], ['-psecret', $file]);
        $this->assertEquals($message, $app->run());

        $app = new MysqlImport(['DB_USER' => 'root', 'DB_PASSWORD' => 'secret'], [$file]);
        $this->assertEquals($message, $app->run());
        $app->drop('yes');
    }

    public function testConnectionProblemWrongPassword()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Connection problem for 'root' on 'mysql' with error: ";

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'wrong'], [$file]);
        $this->assertStringStartsWith($message, $app->run());
    }

    public function testConnectionProblemWrongHost()
    {
        $file = __DIR__.'/fixtures/database.sql';
        $message = "Connection problem for 'root' on 'wrong' with error: ";

        $app = new MysqlImport(['MYSQL_HOST' => 'wrong', 'MYSQL_ROOT_PASSWORD' => 'wrong'], [$file]);
        $this->assertStringStartsWith($message, $app->run());

        $app = new MysqlImport(
            ['MYSQL_HOST' => 'wrong', 'MYSQL_USER' => 'root', 'MYSQL_PASSWORD' => 'wrong'],
            [$file]
        );
        $this->assertStringStartsWith($message, $app->run());
        $this->assertEquals(2, $app->getExitCode());
    }

    public function testMissingSqlFile()
    {
        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], []);
        $this->assertEquals('Required sql file to import.', $app->run());
    }

    public function testNotExistsSqlFile()
    {
        $file = __DIR__.'/fixtures/not_exists.sql';
        $app = new MysqlImport([], ['-psecret', $file]);
        $this->assertEquals("Sql file '{$file}' not found.", $app->run());
    }

    public function testMissingRootPassword()
    {
        $app = new MysqlImport([], [__DIR__.'/fixtures/database.sql']);
        $this->assertEquals('Required at least root password.', $app->run());
    }

    public function testHostPortFix()
    {
        $app = new MysqlImport(['WORDPRESS_DB_HOST' => 'db:10101'], [__DIR__.'/fixtures/database.sql']);
        $this->assertEquals(['host' => 'db', 'port' => 10101, 'database' => 'database'], $app->getInfo());
    }

    public function testDefaultDatabaseName()
    {
        $app = new MysqlImport(['WORDPRESS_DB_PASSWORD' => 'secret'], [__DIR__.'/fixtures/database.sql']);
        $this->assertEquals(['host' => 'mysql', 'port' => 3306, 'database' => 'wordpress'], $app->getInfo());
    }

    public function testSyntaxError()
    {
        $file = __DIR__.'/fixtures/syntax_error.sql';
        $message = 'You have an error in your SQL syntax; check the manual that corresponds to';

        $app = new MysqlImport(['MYSQL_ROOT_PASSWORD' => 'secret'], [$file]);
        $this->assertStringStartsWith($message, $app->run());
    }
}
